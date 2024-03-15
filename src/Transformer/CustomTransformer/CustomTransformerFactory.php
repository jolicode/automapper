<?php

declare(strict_types=1);

namespace AutoMapper\Transformer\CustomTransformer;

use AutoMapper\Metadata\MapperMetadata;
use AutoMapper\Metadata\SourcePropertyMetadata;
use AutoMapper\Metadata\TargetPropertyMetadata;
use AutoMapper\Transformer\PrioritizedTransformerFactoryInterface;
use AutoMapper\Transformer\TransformerFactoryInterface;
use AutoMapper\Transformer\TransformerInterface;

/**
 * @internal
 */
final class CustomTransformerFactory implements PrioritizedTransformerFactoryInterface, TransformerFactoryInterface
{
    public function __construct(
        private readonly CustomTransformersRegistry $customTransformersRegistry,
    ) {
    }

    public function getPriority(): int
    {
        return 256;
    }

    public function getTransformer(SourcePropertyMetadata $source, TargetPropertyMetadata $target, MapperMetadata $metadata): ?TransformerInterface
    {
        $sourceTypes = $source->types;
        $targetTypes = $target->types;

        $customTransformer = $this->customTransformersRegistry->getCustomTransformerClass($metadata, $sourceTypes, $targetTypes, $source->name, $target->name);

        if (null === $customTransformer) {
            return null;
        }

        [$id, $transformer] = $customTransformer;

        if ($transformer instanceof CustomModelTransformerInterface) {
            return new CustomModelTransformer($id);
        }

        if ($transformer instanceof CustomPropertyTransformerInterface) {
            return new CustomPropertyTransformer($id);
        }

        return null;
    }
}
