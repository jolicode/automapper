<?php

declare(strict_types=1);

namespace AutoMapper\Extractor;

use AutoMapper\Exception\LogicException;
use PhpParser\Node\Expr;

/**
 * Write mutator for the `json` target: the property is serialized to the JSON stream, never
 * assigned to a target. It exists so a property is not dropped as unwritable; its value is emitted
 * by the json mapper's generator, so {@see self::getExpression()} is never used.
 *
 * @internal
 */
final readonly class JsonWriteMutator implements WriteMutatorInterface
{
    public function getExpression(Expr $output, Expr $value, bool $byRef = false): Expr
    {
        throw new LogicException('A json property is streamed, not written; its write mutator must not be used.');
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
