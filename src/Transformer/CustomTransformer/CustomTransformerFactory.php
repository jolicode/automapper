<?php

declare(strict_types=1);

namespace AutoMapper\Transformer\CustomTransformer;

use AutoMapper\MapperMetadataInterface;
use AutoMapper\Transformer\PrioritizedTransformerFactoryInterface;
use AutoMapper\Transformer\TransformerInterface;
use AutoMapper\Transformer\TransformerPropertyFactoryInterface;
use Symfony\Component\PropertyInfo\Type;

final class CustomTransformerFactory implements PrioritizedTransformerFactoryInterface, TransformerPropertyFactoryInterface
{
    public function __construct(
        private readonly CustomTransformersRegistry $customTransformersRegistry,
    ) {
    }

    public function getPriority(): int
    {
        return 256;
    }

    /**
     * @param Type[]|null $sourceTypes
     * @param Type[]|null $targetTypes
     */
    public function getPropertyTransformer(?array $sourceTypes, ?array $targetTypes, MapperMetadataInterface $mapperMetadata, string $property): ?TransformerInterface
    {
        if (null === $sourceTypes || null === $targetTypes) {
            return null;
        }

        $customTransformer = $this->customTransformersRegistry->getCustomTransformerClass($mapperMetadata, $sourceTypes, $targetTypes, $property);

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
