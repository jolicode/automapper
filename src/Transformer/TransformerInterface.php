<?php

declare(strict_types=1);

namespace AutoMapper\Transformer;

use AutoMapper\Extractor\PropertyMapping;
use AutoMapper\Generator\UniqueVariableScope;
use PhpParser\Node\Expr;
use PhpParser\Node\Stmt;

/**
 * Transformer tell how to transform a property mapping.
 *
 * @author Joel Wurtz <jwurtz@jolicode.com>
 *
 * @method array{0: Expr, 1: Stmt[]} transform(Expr $input, Expr $target, PropertyMapping $propertyMapping, UniqueVariableScope $uniqueVariableScope, Expr\Variable $source)
 */
interface TransformerInterface
{
    /**
     * Get AST output and expressions for transforming a property mapping given an input.
     *
     * @return array{0: Expr, 1: Stmt[]} First value is the output expression, second value is an array of stmt needed to get the output
     */
    public function transform(Expr $input, Expr $target, PropertyMapping $propertyMapping, UniqueVariableScope $uniqueVariableScope, /* Expr\Variable $source */): array;
}
