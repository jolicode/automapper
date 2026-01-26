<?php

declare(strict_types=1);

namespace AutoMapper\Extractor;

use PhpParser\Node\Arg;
use PhpParser\Node\Expr;

final readonly class MethodWriteMutator implements WriteMutatorInterface
{
    public function __construct(
        public string $writeMethodName,
    ) {
    }

    public function getExpression(Expr $output, Expr $value, bool $byRef = false): Expr
    {
        return new Expr\MethodCall($output, $this->writeMethodName, [
            new Arg($value),
        ]);
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
