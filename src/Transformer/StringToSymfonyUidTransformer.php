<?php

declare(strict_types=1);

namespace AutoMapper\Transformer;

use AutoMapper\Generator\UniqueVariableScope;
use AutoMapper\Metadata\PropertyMetadata;
use PhpParser\Node\Arg;
use PhpParser\Node\Expr;
use PhpParser\Node\Name;

/**
 * Transform a string to a Symfony Uid object.
 *
 * @author Baptiste Leduc <baptiste.leduc@gmail.com>
 *
 * @internal
 */
final readonly class StringToSymfonyUidTransformer implements TransformerInterface
{
    public function __construct(
        private string $className,
    ) {
    }

    public function transform(Expr $input, Expr $target, PropertyMetadata $propertyMapping, UniqueVariableScope $uniqueVariableScope, Expr\Variable $source): array
    {
        /*
         * Create a Symfony Uid object from a string.
         *
         * new \Symfony\Component\Uid\Uuid($input);
         */
        return [
            new Expr\New_(new Name($this->className), [new Arg($input)]),
            [],
        ];
    }
}
