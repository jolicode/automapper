<?php

declare(strict_types=1);

namespace AutoMapper\Transformer;

use AutoMapper\Extractor\PropertyMapping;
use AutoMapper\Generator\UniqueVariableScope;
use AutoMapper\MapperContext;
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

    public function transform(Expr $input, Expr $target, PropertyMapping $propertyMapping, UniqueVariableScope $uniqueVariableScope): array
    {
        $className = \DateTimeInterface::class === $this->className ? \DateTimeImmutable::class : $this->className;

        /*
         * Create a \DateTime[Immutable] object from a string.
         *
         * \DateTimeImmutable::createFromFormat($context[MapperContext::DATETIME_FORMAT] ?? \DateTimeInterface::RFC3339, $input);
         */
        return [new Expr\StaticCall(new Name\FullyQualified($className), 'createFromFormat', [
            new Arg(
                new Expr\BinaryOp\Coalesce(
                    new Expr\ArrayDimFetch(new Expr\Variable('context'), new Scalar\String_(MapperContext::DATETIME_FORMAT)),
                    new Scalar\String_($this->format),
                )
            ),
            new Arg($input),
        ]), []];
    }
}
