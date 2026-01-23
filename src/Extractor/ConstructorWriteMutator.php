<?php

declare(strict_types=1);

namespace AutoMapper\Extractor;

use AutoMapper\Exception\CompileException;
use PhpParser\Node\Expr;

final readonly class ConstructorWriteMutator implements WriteMutatorInterface
{
    public function __construct(
        public \ReflectionParameter $parameter,
    ) {
    }

    public function getExpression(Expr $output, Expr $value, bool $byRef = false): Expr
    {
        throw new CompileException('Invalid accessor for write expression');
    }

    public function getRemoveExpression(Expr $object, Expr $value): ?Expr
    {
        return null;
    }

    public function getHydrateCallback(string $className): ?Expr
    {
        return null;
    }

    public function isAdderRemover(): bool
    {
        return false;
    }
}
