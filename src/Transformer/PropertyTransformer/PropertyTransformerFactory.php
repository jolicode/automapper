<?php

declare(strict_types=1);

namespace AutoMapper\Transformer\PropertyTransformer;

use AutoMapper\Metadata\MapperMetadata;
use AutoMapper\Metadata\SourcePropertyMetadata;
use AutoMapper\Metadata\TargetPropertyMetadata;
use AutoMapper\Transformer\PrioritizedTransformerFactoryInterface;
use AutoMapper\Transformer\TransformerFactoryInterface;
use AutoMapper\Transformer\TransformerInterface;

/**
 * @internal
 */
final class PropertyTransformerFactory implements PrioritizedTransformerFactoryInterface, TransformerFactoryInterface
{
    public function __construct(
        private readonly PropertyTransformerRegistry $propertyTransformerRegistry,
    ) {
    }

    public function getPriority(): int
    {
        return 256;
    }

    public function getTransformer(SourcePropertyMetadata $source, TargetPropertyMetadata $target, MapperMetadata $mapperMetadata): ?TransformerInterface
    {
        $id = $this->propertyTransformerRegistry->getPropertyTransformersForMapper($source, $target, $mapperMetadata);

        if (null === $id) {
            return null;
        }

        return new PropertyTransformer($id);
    }
}
