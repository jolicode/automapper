<?php

declare(strict_types=1);

namespace AutoMapper\Transformer;

use AutoMapper\Generator\UniqueVariableScope;
use AutoMapper\Metadata\PropertyMetadata;
use PhpParser\Node\Expr;
use PhpParser\Node\Name;

/**
 * Transform a BackendEnum into a scalar.
 *
 * @author Baptiste Leduc <baptiste.leduc@gmail.com>
 *
 * @internal
 */
final readonly class SourceEnumTransformer implements TransformerInterface, CheckTypeInterface
{
    public function __construct(
        private ?string $enumClassName = null,
    ) {
    }

    public function transform(Expr $input, Expr $target, PropertyMetadata $propertyMapping, UniqueVariableScope $uniqueVariableScope, Expr $source, ?Expr $existingValue = null): array
    {
        /* $input->value */
        return [new Expr\PropertyFetch($input, 'value'), []];
    }

    public function getCheckExpression(Expr $input, Expr $target, PropertyMetadata $propertyMapping, UniqueVariableScope $uniqueVariableScope, Expr $source): Expr
    {
        /* $input instanceof \Some\Enum */
        return new Expr\Instanceof_($input, new Name\FullyQualified($this->enumClassName ?? \BackedEnum::class));
    }
}
