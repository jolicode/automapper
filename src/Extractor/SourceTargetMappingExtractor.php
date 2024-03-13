<?php

declare(strict_types=1);

namespace AutoMapper\Extractor;

use AutoMapper\Metadata\MapperMetadata;
use AutoMapper\Metadata\PropertyMetadata;
use AutoMapper\Metadata\SourcePropertyMetadata;
use AutoMapper\Metadata\TargetPropertyMetadata;
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
    public function getPropertiesMapping(MapperMetadata $mapperMetadata): array
    {
        $sourceProperties = $this->propertyInfoExtractor->getProperties($mapperMetadata->source);
        $targetProperties = $this->propertyInfoExtractor->getProperties($mapperMetadata->target);

        if (null === $sourceProperties || null === $targetProperties) {
            return [];
        }

        $sourceProperties = array_unique($sourceProperties);
        $targetProperties = array_unique($targetProperties);

        $mapping = [];

        foreach ($sourceProperties as $property) {
            if (!$this->propertyInfoExtractor->isReadable($mapperMetadata->source, $property)) {
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
            if (!$this->propertyInfoExtractor->isWritable($mapperMetadata->target, $property)) {
                continue;
            }

            if (\in_array($property, $sourceProperties, true)) {
                continue;
            }

            if ($propertyMapping = $this->toPropertyMapping($mapperMetadata, $property)) {
                $mapping[] = $propertyMapping;
            }
        }

        return $mapping;
    }

    private function toPropertyMapping(MapperMetadata $mapperMetadata, string $property): ?PropertyMetadata
    {
        $targetMutatorConstruct = $this->getWriteMutator($mapperMetadata->source, $mapperMetadata->target, $property, [
            'enable_constructor_extraction' => true,
        ]);

        if ((null === $targetMutatorConstruct || null === $targetMutatorConstruct->parameter) && !$this->propertyInfoExtractor->isWritable($mapperMetadata->target, $property)) {
            return null;
        }

        $sourceTypes = $this->propertyInfoExtractor->getTypes($mapperMetadata->source, $property) ?? [];
        $targetTypes = $this->propertyInfoExtractor->getTypes($mapperMetadata->target, $property) ?? [];

        $sourceProperty = new SourcePropertyMetadata(
            $sourceTypes,
            $property,
            $this->getReadAccessor($mapperMetadata->source, $mapperMetadata->target, $property),
            false,
            $this->getGroups($mapperMetadata->source, $property),
            $this->isIgnoredProperty($mapperMetadata->source, $property),
            PropertyReadInfo::VISIBILITY_PUBLIC === ($this->readInfoExtractor->getReadInfo($mapperMetadata->source, $property)?->getVisibility() ?? PropertyReadInfo::VISIBILITY_PUBLIC),
            dateTimeFormat: $this->configuration->dateTimeFormat,
        );

        $targetProperty = new TargetPropertyMetadata(
            $targetTypes,
            $property,
            $this->getWriteMutator($mapperMetadata->source, $mapperMetadata->target, $property, [
                'enable_constructor_extraction' => false,
            ]),
            WriteMutator::TYPE_CONSTRUCTOR === $targetMutatorConstruct->type ? $targetMutatorConstruct : null,
            $this->getGroups($mapperMetadata->target, $property),
            $this->isIgnoredProperty($mapperMetadata->target, $property),
            dateTimeFormat: $this->configuration->dateTimeFormat,
        );

        return new PropertyMetadata(
            $sourceProperty,
            $targetProperty,
            $this->guessMaxDepth($mapperMetadata, $property),
        );
    }

    private function guessMaxDepth(MapperMetadata $mapperMetadata, string $property): ?int
    {
        $maxDepthSource = $this->getMaxDepth($mapperMetadata->source, $property);
        $maxDepthTarget = $this->getMaxDepth($mapperMetadata->target, $property);

        return match (true) {
            null !== $maxDepthSource && null !== $maxDepthTarget => min($maxDepthSource, $maxDepthTarget),
            null !== $maxDepthSource => $maxDepthSource,
            null !== $maxDepthTarget => $maxDepthTarget,
            default => null,
        };
    }
}
