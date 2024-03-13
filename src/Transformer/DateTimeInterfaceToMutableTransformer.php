<?php

declare(strict_types=1);

namespace AutoMapper\Transformer;

use AutoMapper\Generator\UniqueVariableScope;
use AutoMapper\Metadata\PropertyMetadata;
use PhpParser\Node\Arg;
use PhpParser\Node\Expr;
use PhpParser\Node\Name;

/**
 * Transform DateTimeInterface to DateTime.
 *
 * @author Joel Wurtz <jwurtz@jolicode.com>
 */
final class DateTimeInterfaceToMutableTransformer implements TransformerInterface
{
    public function transform(Expr $input, Expr $target, PropertyMetadata $propertyMapping, UniqueVariableScope $uniqueVariableScope, /* Expr\Variable $source */): array
    {
        if (\func_num_args() < 5) {
            trigger_deprecation('jolicode/automapper', '8.2', 'The "%s()" method will have a new "Expr\Variable $source" argument in version 9.0, not defining it is deprecated.', __METHOD__);
        }

        /*
         * Handles all DateTime instance types using createFromInterface.
         *
         * \DateTimeImmutable::createFromInterface($input);
         */
        return [
            new Expr\StaticCall(new Name\FullyQualified(\DateTime::class), 'createFromInterface', [
                new Arg($input),
            ]),
            [],
        ];
    }
}
