<?php

declare(strict_types=1);

namespace AutoMapper\Transformer;

use AutoMapper\Generator\UniqueVariableScope;
use AutoMapper\Metadata\PropertyMetadata;
use PhpParser\Node\Expr;
use PhpParser\Node\Name;
use Symfony\Component\Uid\AbstractUid;

/**
 * Transform a \DateTimeInterface object to a string.
 *
 * @author Joel Wurtz <jwurtz@jolicode.com>
 *
 * @internal
 */
final readonly class SymfonyUidToStringTransformer implements TransformerInterface, CheckTypeInterface
{
    public function __construct(
        private bool $isUlid,
    ) {
    }

    public function transform(Expr $input, Expr $target, PropertyMetadata $propertyMapping, UniqueVariableScope $uniqueVariableScope, Expr $source, ?Expr $existingValue = null): array
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

    public function getCheckExpression(Expr $input, Expr $target, PropertyMetadata $propertyMapping, UniqueVariableScope $uniqueVariableScope, Expr $source): Expr
    {
        /* $input instanceof \Symfony\Component\Uid\AbstractUid */
        return new Expr\Instanceof_($input, new Name\FullyQualified(AbstractUid::class));
    }
}
