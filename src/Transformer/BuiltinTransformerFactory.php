<?php

declare(strict_types=1);

namespace AutoMapper\Transformer;

use AutoMapper\Metadata\MapperMetadata;
use AutoMapper\Metadata\SourcePropertyMetadata;
use AutoMapper\Metadata\TargetPropertyMetadata;
use Symfony\Component\TypeInfo\Type;
use Symfony\Component\TypeInfo\TypeIdentifier;

/**
 * @author Joel Wurtz <jwurtz@jolicode.com>
 *
 * @internal
 */
final class BuiltinTransformerFactory implements TransformerFactoryInterface, PrioritizedTransformerFactoryInterface
{
    public function getTransformer(SourcePropertyMetadata $source, TargetPropertyMetadata $target, MapperMetadata $mapperMetadata): ?TransformerInterface
    {
        if (null === $source->type) {
            return null;
        }

        // An enum target is created from the scalar value by the EnumTransformerFactory, which has a lower priority
        if ($target->type instanceof Type\EnumType) {
            return null;
        }

        // We don't want to handle mixed here as we can better guess the type with other transformers
        if ($source->type instanceof Type\BuiltinType && $source->type->getTypeIdentifier() !== TypeIdentifier::MIXED && $source->type->getTypeIdentifier() !== TypeIdentifier::NULL) {
            return new BuiltinTransformer($source->type, $target->type ?? Type::mixed());
        }

        return null;
    }

    public function getPriority(): int
    {
        return 16;
    }
}
