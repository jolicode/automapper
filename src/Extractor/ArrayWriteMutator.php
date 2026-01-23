<?php

declare(strict_types=1);

namespace AutoMapper\Extractor;

use PhpParser\Node\Expr;
use PhpParser\Node\Scalar;

final readonly class ArrayWriteMutator implements WriteMutatorInterface
{
    public function __construct(
        public string $property,
    ) {
    }

    public function getExpression(Expr $output, Expr $value, bool $byRef = false): Expr
    {
        if ($byRef) {
            return new Expr\AssignRef(new Expr\ArrayDimFetch($output, new Scalar\String_($this->property)), $value);
        }

        return new Expr\Assign(new Expr\ArrayDimFetch($output, new Scalar\String_($this->property)), $value);
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
