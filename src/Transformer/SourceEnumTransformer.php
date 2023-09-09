<?php

namespace AutoMapper\Transformer;

use AutoMapper\Extractor\PropertyMapping;
use AutoMapper\Generator\UniqueVariableScope;
use PhpParser\Node\Expr;

/**
 * Transform a BackendEnum into a scalar.
 *
 * @author Baptiste Leduc <baptiste.leduc@gmail.com>
 */
final class SourceEnumTransformer implements TransformerInterface
{
    public function transform(Expr $input, Expr $target, PropertyMapping $propertyMapping, UniqueVariableScope $uniqueVariableScope): array
    {
        return [new Expr\PropertyFetch($input, 'value'), []];
    }
}
