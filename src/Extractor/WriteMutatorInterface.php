<?php

declare(strict_types=1);

namespace AutoMapper\Extractor;

use PhpParser\Node\Expr;

interface WriteMutatorInterface
{
    public function getExpression(Expr $output, Expr $value, bool $byRef = false): Expr;

    public function getRemoveExpression(Expr $object, Expr $value): ?Expr;

    public function getHydrateCallback(string $className): ?Expr;

    public function isAdderRemover(): bool;
}
