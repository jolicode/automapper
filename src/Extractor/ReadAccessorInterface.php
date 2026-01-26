<?php

declare(strict_types=1);

namespace AutoMapper\Extractor;

use PhpParser\Node\Expr;

interface ReadAccessorInterface
{
    public function getExpression(Expr $input, bool $target = false): Expr;

    public function getIsDefinedExpression(Expr\Variable $input, bool $nullable = false, bool $target = false): ?Expr;

    public function getIsNullExpression(Expr\Variable $input, bool $target = false): Expr;

    public function getIsUndefinedExpression(Expr\Variable $input, bool $target = false): Expr;

    public function getExtractCallback(string $className): ?Expr;

    public function getExtractIsNullCallback(string $className): ?Expr;

    public function getExtractIsUndefinedCallback(string $className): ?Expr;
}
