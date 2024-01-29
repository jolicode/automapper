<?php

declare(strict_types=1);

namespace AutoMapper\Generator\TransformerResolver;

use AutoMapper\CustomTransformer\CustomTransformersRegistry;
use AutoMapper\Extractor\PropertyMapping;
use AutoMapper\MapperMetadata\MapperMetadataInterface;
use AutoMapper\MapperMetadata\MapperType;
use AutoMapper\Transformer\TransformerFactoryInterface;
use AutoMapper\Transformer\TransformerInterface;
use Symfony\Component\PropertyInfo\PropertyInfoExtractorInterface;

final readonly class SourceTargetTransformerResolver implements TransformerResolverInterface
{
    public function __construct(
        private PropertyInfoExtractorInterface $propertyInfoExtractor,
        private CustomTransformersRegistry $customTransformerRegistry,
        private TransformerFactoryInterface $transformerFactory,
    )
    {
    }

    public function resolveTransformer(PropertyMapping $propertyMapping): TransformerInterface|string|null
    {
        if ($propertyMapping->mapperMetadata->mapperType() !== MapperType::SOURCE_TARGET) {
            return null;
        }

        $mapperMetadata = $propertyMapping->mapperMetadata;
        $property = $propertyMapping->property;

        $sourceTypes = $this->propertyInfoExtractor->getTypes($mapperMetadata->getSource(), $property) ?? [];
        $targetTypes = $this->propertyInfoExtractor->getTypes($mapperMetadata->getTarget(), $property) ?? [];

        $transformer = $this->customTransformerRegistry->getCustomTransformerClass($mapperMetadata, $sourceTypes, $targetTypes, $property);

        if ($transformer || $this->shouldOUseOnlyCustomTransformer($mapperMetadata, $property)) {
            return $transformer;
        }

        return $this->transformerFactory->getTransformer($sourceTypes, $targetTypes, $mapperMetadata);
    }

    // todo: this could be improved (readability)
    private function shouldOUseOnlyCustomTransformer(MapperMetadataInterface $mapperMetadata, string $property): bool
    {
        $sourceProperties = $this->propertyInfoExtractor->getProperties($mapperMetadata->getSource()) ?? [];
        $targetProperties = $this->propertyInfoExtractor->getProperties($mapperMetadata->getTarget()) ?? [];

        return !\in_array($property, $sourceProperties, true)
            && \in_array($property, $targetProperties, true);
    }
}
