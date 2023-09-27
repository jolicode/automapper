<?php

declare(strict_types=1);

namespace AutoMapper\Transformer;

use AutoMapper\Extractor\PropertyMapping;
use AutoMapper\Generator\UniqueVariableScope;
use PhpParser\Node\Arg;
use PhpParser\Node\Expr;
use PhpParser\Node\Name;

/**
 * Transform a scalar into a BackendEnum.
 *
 * @author Baptiste Leduc <baptiste.leduc@gmail.com>
 */
final readonly class TargetEnumTransformer implements TransformerInterface
{
    public function __construct(
        private string $targetClassName,
    ) {
    }

    public function transform(Expr $input, Expr $target, PropertyMapping $propertyMapping, UniqueVariableScope $uniqueVariableScope): array
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
