<?php

declare(strict_types=1);

namespace AutoMapper\Transformer;

use AutoMapper\Generator\UniqueVariableScope;
use AutoMapper\Metadata\PropertyMetadata;
use PhpParser\Node\Arg;
use PhpParser\Node\Expr;
use PhpParser\Node\Name;

/**
 * Transform DateTimeInterface to DateTimeImmutable.
 *
 * @author Joel Wurtz <jwurtz@jolicode.com>
 *
 * @internal
 */
final class DateTimeInterfaceToImmutableTransformer implements TransformerInterface
{
    public function transform(Expr $input, Expr $target, PropertyMetadata $propertyMapping, UniqueVariableScope $uniqueVariableScope, Expr\Variable $source, ?Expr\Variable $existingValue = null): array
    {
        /*
         * Handles all DateTime instance types using createFromInterface.
         *
         * \DateTimeImmutable::createFromInterface($input);
         */
        return [
            new Expr\StaticCall(new Name\FullyQualified(\DateTimeImmutable::class), 'createFromInterface', [
                new Arg($input),
            ]),
            [],
        ];
    }
}
