<?php

declare(strict_types=1);

namespace AutoMapper\Transformer;

use AutoMapper\Extractor\PropertyMapping;
use AutoMapper\Generator\UniqueVariableScope;
use PhpParser\Node\Expr;

/**
 * Does not do any transformation, output = input.
 *
 * @author Joel Wurtz <jwurtz@jolicode.com>
 */
final class CopyTransformer implements TransformerInterface
{
    public function transform(Expr $input, Expr $target, PropertyMapping $propertyMapping, UniqueVariableScope $uniqueVariableScope, /* Expr\Variable $source */): array
    {
        if (\func_num_args() < 5) {
            trigger_deprecation('jolicode/automapper', '8.2', 'The "%s()" method will have a new "Expr\Variable $source" argument in version 9.0, not defining it is deprecated.', __METHOD__);
        }

        /* No transform here it's the same value and it's a copy so we do not need to clone */
        return [$input, []];
    }
}
