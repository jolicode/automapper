<?php

declare(strict_types=1);

namespace AutoMapper\Transformer;

use AutoMapper\Extractor\PropertyMapping;
use AutoMapper\Generator\UniqueVariableScope;
use PhpParser\Node\Expr;

/**
 * Transform a \DateTimeInterface object to a string.
 *
 * @author Joel Wurtz <jwurtz@jolicode.com>
 */
final readonly class SymfonyUidToStringTransformer implements TransformerInterface
{
    public function __construct(
        private bool $isUlid,
    ) {
    }

    public function transform(Expr $input, Expr $target, PropertyMapping $propertyMapping, UniqueVariableScope $uniqueVariableScope): array
    {
        /*
         * Create a string from a Symfony Uid object.
         *
         * $input->toBase32() or $input->toRfc4122();
         */
        if ($this->isUlid) {
            return [
                // ulid
                new Expr\MethodCall($input, 'toBase32'),
                [],
            ];
        }

        return [
            // uuid
            new Expr\MethodCall($input, 'toRfc4122'),
            [],
        ];
    }
}
