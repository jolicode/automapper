<?php

declare(strict_types=1);

namespace AutoMapper\Transformer;

use AutoMapper\Extractor\PropertyMapping;
use AutoMapper\Generator\UniqueVariableScope;
use AutoMapper\MapperContext;
use PhpParser\Node\Arg;
use PhpParser\Node\Expr;
use PhpParser\Node\Scalar;

/**
 * Transform a \DateTimeInterface object to a string.
 *
 * @author Joel Wurtz <jwurtz@jolicode.com>
 */
final class DateTimeToStringTransformer implements TransformerInterface
{
    public function __construct(
        private readonly string $format = \DateTimeInterface::RFC3339,
    ) {
    }

    public function transform(Expr $input, Expr $target, PropertyMapping $propertyMapping, UniqueVariableScope $uniqueVariableScope, /* Expr\Variable $source */): array
    {
        if (\func_num_args() < 5) {
            trigger_deprecation('jolicode/automapper', '8.2', 'The "%s()" method will have a new "Expr\Variable $source" argument in version 9.0, not defining it is deprecated.', __METHOD__);
        }

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
