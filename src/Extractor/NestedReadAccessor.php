<?php

declare(strict_types=1);

namespace AutoMapper\Extractor;

use PhpParser\Node\Expr;

final readonly class NestedReadAccessor implements ReadAccessorInterface
{
    public function __construct(
        public ReadAccessorInterface $parent,
        public ReadAccessorInterface $child,
    ) {
    }

    public function getExpression(Expr $input, bool $target = false): Expr
    {
        $parentExpr = $this->parent->getExpression($input, $target);

        return $this->child->getExpression($parentExpr, $target);
    }

    public function getIsDefinedExpression(Expr\Variable $input, bool $nullable = false, bool $target = false): ?Expr
    {
        return null;
    }

    public function getIsNullExpression(Expr\Variable $input, bool $target = false): Expr
    {
        return $this->parent->getIsNullExpression($input, $target);
    }

    public function getIsUndefinedExpression(Expr\Variable $input, bool $target = false): Expr
    {
        return $this->parent->getIsNullExpression($input, $target);
    }

    public function getExtractCallback(string $className): ?Expr
    {
        return null;
    }

    public function getExtractIsNullCallback(string $className): ?Expr
    {
        return null;
    }

    public function getExtractIsUndefinedCallback(string $className): ?Expr
    {
        return null;
    }
}
