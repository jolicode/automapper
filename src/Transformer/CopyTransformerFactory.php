<?php

declare(strict_types=1);

namespace AutoMapper\Transformer;

use AutoMapper\Metadata\MapperMetadata;
use AutoMapper\Metadata\SourcePropertyMetadata;
use AutoMapper\Metadata\TargetPropertyMetadata;

/**
 * @internal
 */
final readonly class CopyTransformerFactory implements TransformerFactoryInterface, PrioritizedTransformerFactoryInterface
{
    public function getTransformer(SourcePropertyMetadata $source, TargetPropertyMetadata $target, MapperMetadata $mapperMetadata): ?TransformerInterface
    {
        if ($source->type !== null) {
            return null;
        }

        return new CopyTransformer();
    }

    public function getPriority(): int
    {
        return -64;
    }
}
