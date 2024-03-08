<?php

declare(strict_types=1);

namespace AutoMapper\Extractor;

use AutoMapper\MapperGeneratorMetadataInterface;
use AutoMapper\MapperMetadataInterface;
use Symfony\Component\PropertyInfo\PropertyReadInfo;

/**
 * Extracts mapping between two objects, only gives properties that have the same name.
 *
 * @author Joel Wurtz <jwurtz@jolicode.com>
 *
 * @internal
 */
class SourceTargetMappingExtractor extends MappingExtractor
{
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

            if (!\in_array($property, $targetProperties, true)) {
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

    private function toPropertyMapping(MapperGeneratorMetadataInterface $mapperMetadata, string $property, bool $onlyCustomTransformer = false): ?PropertyMapping
    {
        $targetMutatorConstruct = $this->getWriteMutator($mapperMetadata->getSource(), $mapperMetadata->getTarget(), $property, [
            'enable_constructor_extraction' => true,
        ]);

        if ((null === $targetMutatorConstruct || null === $targetMutatorConstruct->parameter) && !$this->propertyInfoExtractor->isWritable($mapperMetadata->getTarget(), $property)) {
            return null;
        }

        $sourceTypes = $this->propertyInfoExtractor->getTypes($mapperMetadata->getSource(), $property) ?? [];
        $targetTypes = $this->propertyInfoExtractor->getTypes($mapperMetadata->getTarget(), $property) ?? [];

        $transformer = $this->customTransformerRegistry->getCustomTransformerClass($mapperMetadata, $sourceTypes, $targetTypes, $property);

        if (null === $transformer && !$onlyCustomTransformer) {
            $transformer = $this->transformerFactory->getTransformer($sourceTypes, $targetTypes, $mapperMetadata);
        }

        if (null === $transformer) {
            return null;
        }

        return new PropertyMapping(
            $mapperMetadata,
            readAccessor: $this->getReadAccessor($mapperMetadata->getSource(), $mapperMetadata->getTarget(), $property),
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

    private function guessMaxDepth(MapperMetadataInterface $mapperMetadata, string $property): ?int
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
}
