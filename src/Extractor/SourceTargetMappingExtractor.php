<?php

declare(strict_types=1);

namespace AutoMapper\Extractor;

use AutoMapper\Attribute\MapTo;
use AutoMapper\CustomTransformer\CustomTransformerGenerator;
use AutoMapper\CustomTransformer\CustomTransformersRegistry;
use AutoMapper\Generator\TransformerResolver\ChainTransformerResolver;
use AutoMapper\Generator\TransformerResolver\TransformerResolverInterface;
use AutoMapper\MapperMetadata\MapperGeneratorMetadataInterface;
use AutoMapper\MapperMetadata\MapperMetadataInterface;
use AutoMapper\Transformer\TransformerFactoryInterface;
use Symfony\Component\PropertyInfo\PropertyInfoExtractorInterface;
use Symfony\Component\PropertyInfo\PropertyReadInfo;
use Symfony\Component\PropertyInfo\PropertyReadInfoExtractorInterface;
use Symfony\Component\PropertyInfo\PropertyWriteInfoExtractorInterface;
use Symfony\Component\Serializer\Mapping\Factory\ClassMetadataFactoryInterface;

/**
 * Extracts mapping between two objects, only gives properties that have the same name.
 *
 * @author Joel Wurtz <jwurtz@jolicode.com>
 *
 * @internal
 */
class SourceTargetMappingExtractor extends MappingExtractor
{
    public function __construct(
        PropertyInfoExtractorInterface $propertyInfoExtractor,
        PropertyReadInfoExtractorInterface $readInfoExtractor,
        PropertyWriteInfoExtractorInterface $writeInfoExtractor,
        TransformerFactoryInterface $transformerFactory,
        CustomTransformersRegistry $customTransformerRegistry,
        ClassMetadataFactoryInterface $classMetadataFactory = null,
    ) {
        parent::__construct($propertyInfoExtractor, $readInfoExtractor, $writeInfoExtractor, $transformerFactory, $customTransformerRegistry, $classMetadataFactory);
    }

    public function getPropertiesMapping(MapperGeneratorMetadataInterface $mapperMetadata): array
    {
        $sourceProperties = $this->propertyInfoExtractor->getProperties($mapperMetadata->getSource());
        $targetProperties = $this->propertyInfoExtractor->getProperties($mapperMetadata->getTarget());

        if (null === $sourceProperties || null === $targetProperties) {
            return [];
        }

        $sourceProperties = array_unique($sourceProperties);
        $targetProperties = array_unique($targetProperties);

        $mapping = [];

        foreach ([...$sourceProperties, ...$targetProperties] as $property) {
            if (isset($mapping[$property])) {
                continue;
            }

            $targetMutatorConstruct = $this->getWriteMutator($mapperMetadata->getSource(), $mapperMetadata->getTarget(), $property, [
                'enable_constructor_extraction' => true,
            ]);

            $mapping[$property] = new PropertyMapping(
                $mapperMetadata,
                readAccessor: $this->getReadAccessor($mapperMetadata->getSource(), $mapperMetadata->getTarget(), $property),
                writeMutator: $this->getWriteMutator($mapperMetadata->getSource(), $mapperMetadata->getTarget(), $property, [
                    'enable_constructor_extraction' => false,
                ]),
                writeMutatorConstructor: $targetMutatorConstruct && WriteMutator::TYPE_CONSTRUCTOR === $targetMutatorConstruct->type ? $targetMutatorConstruct : null,
                property: $property,
                checkExists: false,
                sourceGroups: $this->getGroups($mapperMetadata->getSource(), $property),
                targetGroups: $this->getGroups($mapperMetadata->getTarget(), $property),
                maxDepth: $this->guessMaxDepth($mapperMetadata, $property),
                sourceIgnored: $this->isIgnoredProperty($mapperMetadata->getSource(), $property),
                targetIgnored: $this->isIgnoredProperty($mapperMetadata->getTarget(), $property),
                isPublic: PropertyReadInfo::VISIBILITY_PUBLIC === ($this->readInfoExtractor->getReadInfo($mapperMetadata->getSource(), $property)?->getVisibility() ?? PropertyReadInfo::VISIBILITY_PUBLIC),
            );
        }

        return $mapping;
    }

    private function guessMaxDepth(MapperMetadataInterface $mapperMetadata, string $property): int|null
    {
        $maxDepthSource = $this->getMaxDepth($mapperMetadata->getSource(), $property);
        $maxDepthTarget = $this->getMaxDepth($mapperMetadata->getTarget(), $property);

        return match (true) {
            null !== $maxDepthSource && null !== $maxDepthTarget => min($maxDepthSource, $maxDepthTarget),
            null !== $maxDepthSource => $maxDepthSource,
            null !== $maxDepthTarget => $maxDepthTarget,
            default => null
        };
    }

    private function propertyHasMapToAttribute(MapperMetadataInterface $mapperMetadata, string $property): MapTo|null
    {
        $sourceReflectionClass = new \ReflectionClass($mapperMetadata->getSource());

        try {
            $reflectionProperty = $sourceReflectionClass->getProperty($property);
        } catch (\ReflectionException $e) {
            return null;
        }

        $attributes = $reflectionProperty->getAttributes(MapTo::class);
        foreach ($attributes as $attribute) {
            /** @var MapTo $attributeInstance */
            $attributeInstance = $attribute->newInstance();
            if (!$attributeInstance->target || $attributeInstance->target === $mapperMetadata->getTarget()) {
                return $attributeInstance;
            }
        }

        return null;
    }
}
