<?php

declare(strict_types=1);

namespace AutoMapper\Transformer;

use PhpParser\Node\Expr;

/**
 * @internal
 */
interface IdentifiersEqualInterface
{
    /**
     * Return an expression to check the if source and target identifiers are equal at runtime.
     *
     * As an example for a string type:
     * ```php
     * is_string($input)
     * ```
     */
    public function getAreIdentifiersEqualExpression(Expr $source, Expr $target): Expr;
}
