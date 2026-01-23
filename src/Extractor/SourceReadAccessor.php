<?php

declare(strict_types=1);

namespace AutoMapper\Extractor;

use PhpParser\Node\Expr;
use PhpParser\Node\Name;

final readonly class SourceReadAccessor implements ReadAccessorInterface
{
    public function getExpression(Expr $input, bool $target = false): Expr
    {
        return $input;
    }

    public function getIsDefinedExpression(Expr\Variable $input, bool $nullable = false, bool $target = false): ?Expr
    {
        return null;
    }

    public function getIsNullExpression(Expr\Variable $input, bool $target = false): Expr
    {
        return new Expr\BinaryOp\Identical(
            new Expr\ConstFetch(new Name('null')),
            $input,
        );
    }

    public function getIsUndefinedExpression(Expr\Variable $input, bool $target = false): Expr
    {
        return new Expr\ConstFetch(new Name('false'));
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
