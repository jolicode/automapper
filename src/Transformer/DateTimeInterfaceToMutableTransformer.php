<?php

declare(strict_types=1);

namespace AutoMapper\Transformer;

use AutoMapper\Extractor\PropertyMapping;
use AutoMapper\Generator\UniqueVariableScope;
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
    public function transform(Expr $input, Expr $target, PropertyMapping $propertyMapping, UniqueVariableScope $uniqueVariableScope): array
    {
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
