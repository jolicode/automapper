<?php

declare(strict_types=1);

namespace AutoMapper\Transformer;

use AutoMapper\Generator\UniqueVariableScope;
use AutoMapper\Metadata\PropertyMetadata;
use PhpParser\Node\Expr;

/**
 * Transform an Enum into a copied Enum.
 *
 * @author Baptiste Leduc <baptiste.leduc@gmail.com>
 */
final class CopyEnumTransformer implements TransformerInterface
{
    public function transform(Expr $input, Expr $target, PropertyMetadata $propertyMapping, UniqueVariableScope $uniqueVariableScope, /* Expr\Variable $source */): array
    {
        if (\func_num_args() < 5) {
            trigger_deprecation('jolicode/automapper', '8.2', 'The "%s()" method will have a new "Expr\Variable $source" argument in version 9.0, not defining it is deprecated.', __METHOD__);
        }

        /* No transform here it's the same value and it's a copy so we do not need to clone */
        return [$input, []];
    }
}
