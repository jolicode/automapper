<?php

declare(strict_types=1);

namespace AutoMapper\Transformer;

use AutoMapper\Generator\UniqueVariableScope;
use AutoMapper\Metadata\PropertyMetadata;
use PhpParser\Node\Expr;

/**
 * @internal
 */
interface CheckTypeInterface
{
    /**
     * Return an expression to check the input type at runtime.
     *
     * As an example for a string type:
     * ```php
     * is_string($input)
     * ```
     */
    public function getCheckExpression(Expr $input, Expr $target, PropertyMetadata $propertyMapping, UniqueVariableScope $uniqueVariableScope, Expr $source): ?Expr;
}
