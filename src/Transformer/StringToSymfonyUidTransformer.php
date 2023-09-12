<?php

declare(strict_types=1);

namespace AutoMapper\Transformer;

use AutoMapper\Extractor\PropertyMapping;
use AutoMapper\Generator\UniqueVariableScope;
use PhpParser\Node\Arg;
use PhpParser\Node\Expr;
use PhpParser\Node\Name;

/**
 * Transform a string to a Symfony Uid object.
 *
 * @author Baptiste Leduc <baptiste.leduc@gmail.com>
 */
final readonly class StringToSymfonyUidTransformer implements TransformerInterface
{
    public function __construct(
        private string $className,
    ) {
    }

    public function transform(Expr $input, Expr $target, PropertyMapping $propertyMapping, UniqueVariableScope $uniqueVariableScope): array
    {
        return [
            new Expr\New_(new Name($this->className), [new Arg($input)]),
            [],
        ];
    }
}
