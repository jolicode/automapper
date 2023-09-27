<?php

declare(strict_types=1);

namespace AutoMapper\Transformer;

use AutoMapper\Extractor\PropertyMapping;
use AutoMapper\Generator\UniqueVariableScope;
use PhpParser\Node\Expr;

/**
 * Transform an Enum into a copied Enum.
 *
 * @author Baptiste Leduc <baptiste.leduc@gmail.com>
 */
final class CopyEnumTransformer implements TransformerInterface
{
    public function transform(Expr $input, Expr $target, PropertyMapping $propertyMapping, UniqueVariableScope $uniqueVariableScope): array
    {
        /* No transform here it's the same value and it's a copy so we do not need to clone */
        return [$input, []];
    }
}
