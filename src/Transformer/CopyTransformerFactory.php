<?php

declare(strict_types=1);

namespace AutoMapper\Transformer;

use AutoMapper\Metadata\MapperMetadata;
use AutoMapper\Metadata\SourcePropertyMetadata;
use AutoMapper\Metadata\TargetPropertyMetadata;
use AutoMapper\Metadata\TypesMatching;

/**
 * @internal
 */
final readonly class CopyTransformerFactory implements TransformerFactoryInterface, PrioritizedTransformerFactoryInterface
{
    public function getTransformer(TypesMatching $types, SourcePropertyMetadata $source, TargetPropertyMetadata $target, MapperMetadata $mapperMetadata): ?TransformerInterface
    {
        if (\count($types) >= 1) {
            return null;
        }

        return new CopyTransformer();
    }

    public function getPriority(): int
    {
        return -64;
    }
}
