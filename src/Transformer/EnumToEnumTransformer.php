<?php

declare(strict_types=1);

namespace AutoMapper\Transformer;

use AutoMapper\Generator\UniqueVariableScope;
use AutoMapper\Metadata\PropertyMetadata;
use PhpParser\Node\Arg;
use PhpParser\Node\Expr;
use PhpParser\Node\Name;

/**
 * Transform a BackedEnum into another BackedEnum through their backing value.
 *
 * @author Joel Wurtz <jwurtz@jolicode.com>
 *
 * @internal
 */
final readonly class EnumToEnumTransformer implements TransformerInterface, CheckTypeInterface
{
    public function __construct(
        private string $targetClassName,
        private ?string $sourceClassName = null,
    ) {
    }

    public function transform(Expr $input, Expr $target, PropertyMetadata $propertyMapping, UniqueVariableScope $uniqueVariableScope, Expr $source, ?Expr $existingValue = null): array
    {
        /*
         * Transform a BackedEnum into another BackedEnum.
         *
         * \Backed\Enum\TargetEnum::from($input->value);
         */
        return [new Expr\StaticCall(new Name\FullyQualified($this->targetClassName), 'from', [
            new Arg(new Expr\PropertyFetch($input, 'value')),
        ]), []];
    }

    public function getCheckExpression(Expr $input, Expr $target, PropertyMetadata $propertyMapping, UniqueVariableScope $uniqueVariableScope, Expr $source): Expr
    {
        /* $input instanceof \Some\Enum */
        return new Expr\Instanceof_($input, new Name\FullyQualified($this->sourceClassName ?? \BackedEnum::class));
    }
}
