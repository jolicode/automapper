<?php

declare(strict_types=1);

namespace AutoMapper\Transformer;

use PhpParser\Node\Expr;

/**
 * @internal
 */
interface IdentifierHashInterface
{
    /**
     * Return an expression to get the hash of the source.
     *
     * As an example for a string type:
     * ```php
     * hash('sha256', $input)
     * ```
     */
    public function getSourceHashExpression(Expr $source): Expr;

    /**
     * Return an expression to get the hash of the target.
     *
     * As an example for a string type:
     * ```php
     * hash('sha256', $input)
     * ```
     */
    public function getTargetHashExpression(Expr $target): Expr;

    /**
     * Return an expression to get identifier of the target.
     *
     * As an example:
     * ```php
     * return $input->id;
     * ```
     */
    public function getIdentifierExpression(Expr $input): Expr;
}
