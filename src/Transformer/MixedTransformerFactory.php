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
final class MixedTransformerFactory implements TransformerFactoryInterface, PrioritizedTransformerFactoryInterface
{
    public function getTransformer(SourcePropertyMetadata $source, TargetPropertyMetadata $target, MapperMetadata $mapperMetadata): ?TransformerInterface
    {
        if (null === $source->type) {
            return null;
        }

        if ($source->type instanceof Type\BuiltinType && $source->type->getTypeIdentifier() === TypeIdentifier::MIXED) {
            return new BuiltinTransformer($source->type, $target->type ?? Type::mixed());
        }

        return null;
    }

    public function getPriority(): int
    {
        return -32;
    }
}
