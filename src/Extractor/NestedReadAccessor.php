<?php

declare(strict_types=1);

namespace AutoMapper\Extractor;

use PhpParser\Node\Arg;
use PhpParser\Node\Expr;
use PhpParser\Node\Name;
use PhpParser\Node\Scalar;

final readonly class NestedReadAccessor implements ReadAccessorInterface
{
    public function __construct(
        public ReadAccessorInterface $parent,
        public ReadAccessorInterface $child,
        public ?string $childClass = null,
        public ?string $path = null,
    ) {
    }

    public function getExpression(Expr $input, bool $target = false): Expr
    {
        $parentExpr = $this->parent->getExpression($input, $target);

        if ($this->childUsesExtractCallback() && null !== $this->path) {
            /*
             * The child property is private, use the extract callback registered under the full path,
             * which reads the value from the parent object
             *
             * $this->extractCallbacks['parent.child']($value->parent)
             */
            return new Expr\FuncCall(
                new Expr\ArrayDimFetch(new Expr\PropertyFetch(new Expr\Variable('this'), $target ? MethodReadAccessor::EXTRACT_TARGET_CALLBACK : MethodReadAccessor::EXTRACT_CALLBACK), new Scalar\String_($this->path)),
                [
                    new Arg($parentExpr),
                ]
            );
        }

        return $this->child->getExpression($parentExpr, $target);
    }

    public function getIsDefinedExpression(Expr $input, bool $nullable = false, bool $target = false): Expr
    {
        $parentDefined = $this->getParentDefinedExpression($input, $target);
        $childDefined = $this->childUsesExtractCallback()
            ? null
            : $this->child->getIsDefinedExpression($this->parent->getExpression($input, $target), $nullable, $target);

        if (null === $childDefined) {
            return $parentDefined;
        }

        return new Expr\BinaryOp\BooleanAnd($parentDefined, $childDefined);
    }

    public function getIsNullExpression(Expr $input, bool $target = false): Expr
    {
        $parentExpr = $this->parent->getExpression($input, $target);

        if ($this->childUsesExtractCallback() && null !== $this->path) {
            /* !<parent is defined> || null === $this->extractCallbacks['parent.child']($value->parent) */
            $childIsNull = new Expr\BinaryOp\Identical(
                new Expr\ConstFetch(new Name('null')),
                $this->getExpression($input, $target),
            );
        } else {
            $childIsNull = $this->child->getIsNullExpression($parentExpr, $target);
        }

        /*
         * The nested value is considered null when the parent cannot be accessed or when the child value is null
         *
         * !isset($value->parent) || <child is null on $value->parent>
         */
        return new Expr\BinaryOp\BooleanOr(
            new Expr\BooleanNot($this->getParentDefinedExpression($input, $target)),
            $childIsNull,
        );
    }

    public function getIsUndefinedExpression(Expr $input, bool $target = false): Expr
    {
        $parentExpr = $this->parent->getExpression($input, $target);

        $childUndefined = $this->childUsesExtractCallback()
            ? new Expr\ConstFetch(new Name('false'))
            : $this->child->getIsUndefinedExpression($parentExpr, $target);

        /*
         * The nested value is undefined when the parent cannot be accessed or when the child value is undefined
         *
         * !isset($value->parent) || <child is undefined on $value->parent>
         */
        return new Expr\BinaryOp\BooleanOr(
            new Expr\BooleanNot($this->getParentDefinedExpression($input, $target)),
            $childUndefined,
        );
    }

    public function getExtractCallback(string $className): ?Expr
    {
        if (null === $this->childClass) {
            return null;
        }

        // the callback reads the child property from the parent object
        return $this->child->getExtractCallback($this->childClass);
    }

    public function getExtractIsNullCallback(string $className): ?Expr
    {
        return null;
    }

    public function getExtractIsUndefinedCallback(string $className): ?Expr
    {
        return null;
    }

    /**
     * Expression checking that the parent value can be safely accessed: it must be defined and not null.
     */
    private function getParentDefinedExpression(Expr $input, bool $target = false): Expr
    {
        return $this->parent->getIsDefinedExpression($input, false, $target)
            ?? new Expr\BinaryOp\NotIdentical(new Expr\ConstFetch(new Name('null')), $this->parent->getExpression($input, $target));
    }

    private function childUsesExtractCallback(): bool
    {
        if ($this->child instanceof PropertyReadAccessor || $this->child instanceof MethodReadAccessor) {
            return $this->child->private;
        }

        return false;
    }
}
