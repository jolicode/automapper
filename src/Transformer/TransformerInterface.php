<?php

declare(strict_types=1);

namespace AutoMapper\Transformer;

use AutoMapper\Generator\UniqueVariableScope;
use AutoMapper\Metadata\PropertyMetadata;
use PhpParser\Node\Expr;
use PhpParser\Node\Stmt;

/**
 * Transformer tell how to transform a property mapping.
 *
 * @author Joel Wurtz <jwurtz@jolicode.com>
 */
interface TransformerInterface
{
    /**
     * Get AST output and expressions for transforming a property mapping given an input.
     *
     * @return array{0: Expr, 1: Stmt[]} First value is the output expression, second value is an array of stmt needed to get the output
     */
    public function transform(Expr $input, Expr $target, PropertyMetadata $propertyMapping, UniqueVariableScope $uniqueVariableScope, Expr\Variable $source): array;
}
