<?php

declare(strict_types=1);

namespace AutoMapper\Transformer;

use AutoMapper\Generator\UniqueVariableScope;
use AutoMapper\MapperContext;
use AutoMapper\Metadata\PropertyMetadata;
use PhpParser\Node\Arg;
use PhpParser\Node\Expr;
use PhpParser\Node\Scalar;

/**
 * Transform a \DateTimeInterface object to a string.
 *
 * @author Joel Wurtz <jwurtz@jolicode.com>
 *
 * @internal
 */
final class DateTimeToStringTransformer implements TransformerInterface
{
    public function __construct(
        private readonly string $format = \DateTimeInterface::RFC3339,
    ) {
    }

    public function transform(Expr $input, Expr $target, PropertyMetadata $propertyMapping, UniqueVariableScope $uniqueVariableScope, Expr\Variable $source): array
    {
        /*
         * Format the date time object to a string.
         *
         * $input->format($context[MapperContext::DATETIME_FORMAT] ?? \DateTimeInterface::RFC3339);
         */
        return [new Expr\MethodCall($input, 'format', [
            new Arg(
                new Expr\BinaryOp\Coalesce(
                    new Expr\ArrayDimFetch(new Expr\Variable('context'), new Scalar\String_(MapperContext::DATETIME_FORMAT)),
                    new Scalar\String_($this->format),
                )
            ),
        ]), []];
    }
}
