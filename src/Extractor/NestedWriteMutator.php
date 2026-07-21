<?php

declare(strict_types=1);

namespace AutoMapper\Extractor;

use PhpParser\Node\Expr;
use PhpParser\Node\Name;

final readonly class NestedWriteMutator implements WriteMutatorInterface
{
    public function __construct(
        public ReadAccessorInterface $accessor,
        public WriteMutatorInterface $mutator,
    ) {
    }

    public function getExpression(Expr $output, Expr $value, bool $byRef = false): Expr
    {
        $accessExpr = $this->accessor->getExpression($output);
        $writeExpr = $this->mutator->getExpression($accessExpr, $value, $byRef);

        return $this->guardedExpression($output, $writeExpr);
    }

    public function getRemoveExpression(Expr $object, Expr $value): ?Expr
    {
        $accessExpr = $this->accessor->getExpression($object);
        $removeExpr = $this->mutator->getRemoveExpression($accessExpr, $value);

        if (null === $removeExpr) {
            return null;
        }

        return $this->guardedExpression($object, $removeExpr);
    }

    /**
     * Only write to the nested value when the parent value can be safely accessed.
     *
     * ```php
     * isset($result->parent) ? <write expression> : null
     * ```
     */
    private function guardedExpression(Expr $object, Expr $expression): Expr
    {
        // array parents are auto-vivified by the write expression, no guard needed
        if ($this->accessor instanceof ArrayReadAccessor) {
            return $expression;
        }

        $guard = $this->accessor->getIsDefinedExpression($object);

        if (null === $guard) {
            return $expression;
        }

        return new Expr\Ternary($guard, $expression, new Expr\ConstFetch(new Name('null')));
    }

    public function getHydrateCallback(string $className): ?Expr
    {
        // @TODO Handle this case when sub mutator requires hydration
        return null;
    }

    public function isAdderRemover(): bool
    {
        return $this->mutator->isAdderRemover();
    }
}
