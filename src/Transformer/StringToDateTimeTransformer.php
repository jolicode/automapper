<?php

declare(strict_types=1);

namespace AutoMapper\Transformer;

use AutoMapper\Generator\UniqueVariableScope;
use AutoMapper\MapperContext;
use AutoMapper\Metadata\PropertyMetadata;
use PhpParser\Node\Arg;
use PhpParser\Node\Expr;
use PhpParser\Node\Name;
use PhpParser\Node\Scalar;

/**
 * Transform a string to a \DateTimeInterface object.
 *
 * @author Joel Wurtz <jwurtz@jolicode.com>
 */
final readonly class StringToDateTimeTransformer implements TransformerInterface
{
    public function __construct(
        private string $className,
        private string $format = \DateTimeInterface::RFC3339,
    ) {
    }

    public function transform(Expr $input, Expr $target, PropertyMetadata $propertyMapping, UniqueVariableScope $uniqueVariableScope, /* Expr\Variable $source */): array
    {
        if (\func_num_args() < 5) {
            trigger_deprecation('jolicode/automapper', '8.2', 'The "%s()" method will have a new "Expr\Variable $source" argument in version 9.0, not defining it is deprecated.', __METHOD__);
        }

        $className = \DateTimeInterface::class === $this->className ? \DateTimeImmutable::class : $this->className;

        /*
         * Create a \DateTime[Immutable] object from a string.
         *
         * ```php
         * \DateTimeImmutable::createFromFormat(
         *      $context[MapperContext::DATETIME_FORMAT] ?? \DateTimeInterface::RFC3339,
         *      $input,
         *      MapperContext::getForcedTimezone($context)
         * );
         * ```
         */
        return [new Expr\StaticCall(new Name\FullyQualified($className), 'createFromFormat', [
            new Arg(
                new Expr\BinaryOp\Coalesce(
                    new Expr\ArrayDimFetch(new Expr\Variable('context'), new Scalar\String_(MapperContext::DATETIME_FORMAT)),
                    new Scalar\String_($this->format),
                )
            ),
            new Arg($input),
            new Arg(
                new Expr\StaticCall(new Name(MapperContext::class), 'getForcedTimezone', [new Arg(new Expr\Variable('context'))])
            ),
        ]), []];
    }
}
