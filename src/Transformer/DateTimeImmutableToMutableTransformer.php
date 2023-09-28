<?php

declare(strict_types=1);

namespace AutoMapper\Transformer;

use AutoMapper\Extractor\PropertyMapping;
use AutoMapper\Generator\UniqueVariableScope;
use PhpParser\Node\Arg;
use PhpParser\Node\Expr;
use PhpParser\Node\Name;
use PhpParser\Node\Scalar\String_;

/**
 * Transform DateTimeImmutable to DateTime.
 *
 * @author Joel Wurtz <jwurtz@jolicode.com>
 */
final class DateTimeImmutableToMutableTransformer implements TransformerInterface
{
    public function transform(Expr $input, Expr $target, PropertyMapping $propertyMapping, UniqueVariableScope $uniqueVariableScope): array
    {
        /*
         * In case of immutable source we clone the value by using format into a new mutable DateTime.
         *
         * \DateTime::createFromFormat(\DateTime::RFC3339, $input->format(\DateTime::RFC3339));
         */
        return [
            new Expr\StaticCall(new Name\FullyQualified(\DateTime::class), 'createFromFormat', [
                new Arg(new String_(\DateTime::RFC3339)),
                new Arg(new Expr\MethodCall($input, 'format', [
                    new Arg(new String_(\DateTime::RFC3339)),
                ])),
            ]),
            [],
        ];
    }
}
