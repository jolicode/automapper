<?php

declare(strict_types=1);

namespace AutoMapper\Transformer;

use AutoMapper\Generator\UniqueVariableScope;
use AutoMapper\Metadata\PropertyMetadata;
use PhpParser\Node\Arg;
use PhpParser\Node\Expr;
use PhpParser\Node\Name;

/**
 * Transform a scalar into a BackendEnum.
 *
 * @author Baptiste Leduc <baptiste.leduc@gmail.com>
 *
 * @internal
 */
final readonly class TargetEnumTransformer implements TransformerInterface
{
    public function __construct(
        private string $targetClassName,
    ) {
    }

    public function transform(Expr $input, Expr $target, PropertyMetadata $propertyMapping, UniqueVariableScope $uniqueVariableScope, Expr $source, ?Expr $existingValue = null): array
    {
        /*
         * Transform a string into a BackendEnum.
         *
         * \Backend\Enum\TargetEnum::from($input);
         */
        return [new Expr\StaticCall(new Name\FullyQualified($this->targetClassName), 'from', [
            new Arg($input),
        ]), []];
    }
}
