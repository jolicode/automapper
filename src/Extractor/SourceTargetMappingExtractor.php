<?php

declare(strict_types=1);

namespace AutoMapper\Extractor;

use AutoMapper\Attribute\MapTo;
use AutoMapper\CustomTransformer\CustomTransformerGenerator;
use AutoMapper\CustomTransformer\CustomTransformersRegistry;
use AutoMapper\MapperGeneratorMetadataInterface;
use AutoMapper\MapperMetadataInterface;
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
    private CustomTransformerGenerator $customTransformerGenerator;

    public function __construct(
        PropertyInfoExtractorInterface $propertyInfoExtractor,
        PropertyReadInfoExtractorInterface $readInfoExtractor,
        PropertyWriteInfoExtractorInterface $writeInfoExtractor,
        TransformerFactoryInterface $transformerFactory,
        CustomTransformersRegistry $customTransformerRegistry,
        ClassMetadataFactoryInterface $classMetadataFactory = null,
    ) {
        $this->customTransformerGenerator = new CustomTransformerGenerator($customTransformerRegistry);

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

        foreach ($sourceProperties as $property) {
            if (!$this->propertyInfoExtractor->isReadable($mapperMetadata->getSource(), $property)) {
                continue;
            }

            if (!$this->propertyHasMapToAttribute($mapperMetadata, $property) && !\in_array($property, $targetProperties, true)) {
                continue;
            }

            if ($propertyMapping = $this->toPropertyMapping($mapperMetadata, $property)) {
                $mapping[] = $propertyMapping;
            }
        }

        // let's loop over target properties which are not automatically mapped to a source property:
        // this would eventually allow finding custom transformers which only operate on target properties
        foreach ($targetProperties as $property) {
            if (!$this->propertyInfoExtractor->isWritable($mapperMetadata->getTarget(), $property)) {
                continue;
            }

            if (\in_array($property, $sourceProperties, true)) {
                continue;
            }

            if ($propertyMapping = $this->toPropertyMapping($mapperMetadata, $property, onlyCustomTransformer: true)) {
                $mapping[] = $propertyMapping;
            }
        }

        return $mapping;
    }

    private function toPropertyMapping(MapperGeneratorMetadataInterface $mapperMetadata, string $property, bool $onlyCustomTransformer = false): PropertyMapping|null
    {
        $readAccessor = $this->getReadAccessor($mapperMetadata->getSource(), $mapperMetadata->getTarget(), $property);

        if (!$onlyCustomTransformer && ($mapToAttribute = $this->propertyHasMapToAttribute($mapperMetadata, $property)) && $readAccessor) {
            $generatedCustomTransformer = $this->customTransformerGenerator->generateMapToCustomTransformer(
                $mapperMetadata->getSource(),
                $mapperMetadata->getTarget(),
                $property,
                $mapToAttribute->propertyName,
                $readAccessor
            );
        }

        $targetMutatorConstruct = $this->getWriteMutator($mapperMetadata->getSource(), $mapperMetadata->getTarget(), $property, [
            'enable_constructor_extraction' => true,
        ]);

        if ((null === $targetMutatorConstruct || null === $targetMutatorConstruct->parameter) && !$this->propertyInfoExtractor->isWritable($mapperMetadata->getTarget(), $property)) {
            return null;
        }

        $sourceTypes = $this->propertyInfoExtractor->getTypes($mapperMetadata->getSource(), $property) ?? [];
        $targetTypes = $this->propertyInfoExtractor->getTypes($mapperMetadata->getTarget(), $property) ?? [];

        $transformer = $generatedCustomTransformer ?? $this->customTransformerRegistry->getCustomTransformerClass($mapperMetadata, $sourceTypes, $targetTypes, $property);

        if (null === $transformer && !$onlyCustomTransformer) {
            $transformer = $this->transformerFactory->getTransformer($sourceTypes, $targetTypes, $mapperMetadata);
        }

        if (null === $transformer) {
            return null;
        }

        return new PropertyMapping(
            $mapperMetadata,
            readAccessor: $readAccessor,
            writeMutator: $this->getWriteMutator($mapperMetadata->getSource(), $mapperMetadata->getTarget(), $property, [
                'enable_constructor_extraction' => false,
            ]),
            writeMutatorConstructor: WriteMutator::TYPE_CONSTRUCTOR === $targetMutatorConstruct->type ? $targetMutatorConstruct : null,
            transformer: $transformer,
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
