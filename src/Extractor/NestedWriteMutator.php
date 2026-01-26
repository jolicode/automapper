<?php

declare(strict_types=1);

namespace AutoMapper\Extractor;

use PhpParser\Node\Expr;

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

        return $this->mutator->getExpression($accessExpr, $value, $byRef);
    }

    public function getRemoveExpression(Expr $object, Expr $value): ?Expr
    {
        $accessExpr = $this->accessor->getExpression($object);

        return $this->mutator->getRemoveExpression($accessExpr, $value);
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
