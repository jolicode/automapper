<?php

declare(strict_types=1);

namespace AutoMapper\Extractor;

use PhpParser\Node\Arg;
use PhpParser\Node\Expr;

final readonly class AddRemoveWriteMutator implements WriteMutatorInterface
{
    public function __construct(
        public string $addMethodName,
        public string $removeMethodName,
    ) {
    }

    public function getExpression(Expr $output, Expr $value, bool $byRef = false): Expr
    {
        return new Expr\MethodCall($output, $this->addMethodName, [
            new Arg($value),
        ]);
    }

    public function getRemoveExpression(Expr $object, Expr $value): ?Expr
    {
        return new Expr\MethodCall($object, $this->removeMethodName, [
            new Arg($value),
        ]);
    }

    public function getHydrateCallback(string $className): ?Expr
    {
        return null;
    }

    public function isAdderRemover(): bool
    {
        return true;
    }
}
