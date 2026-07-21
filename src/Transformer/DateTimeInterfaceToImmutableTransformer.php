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
final readonly class DateTimeInterfaceToImmutableTransformer implements TransformerInterface, CheckTypeInterface
{
    /**
     * @param class-string $className a \DateTimeImmutable class or one of its subclasses
     */
    public function __construct(
        private string $className = \DateTimeImmutable::class,
    ) {
    }

    public function transform(Expr $input, Expr $target, PropertyMetadata $propertyMapping, UniqueVariableScope $uniqueVariableScope, Expr $source, ?Expr $existingValue = null): array
    {
        /*
         * Handles all DateTime instance types using createFromInterface, keeping the concrete target class.
         *
         * \DateTimeImmutable::createFromInterface($input);
         */
        return [
            new Expr\StaticCall(new Name\FullyQualified($this->className), 'createFromInterface', [
                new Arg($input),
            ]),
            [],
        ];
    }

    public function getCheckExpression(Expr $input, Expr $target, PropertyMetadata $propertyMapping, UniqueVariableScope $uniqueVariableScope, Expr $source): Expr
    {
        /* $input instanceof \DateTimeInterface */
        return new Expr\Instanceof_($input, new Name\FullyQualified(\DateTimeInterface::class));
    }
}
