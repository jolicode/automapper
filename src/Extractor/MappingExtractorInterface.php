<?php

declare(strict_types=1);

namespace AutoMapper\Extractor;

use AutoMapper\MapperGeneratorMetadataInterface;

/**
 * Extracts mapping.
 *
 * @internal
 *
 * @author Joel Wurtz <jwurtz@jolicode.com>
 */
interface MappingExtractorInterface
{
    /**
     * Extracts properties mapped for a given source and target.
     *
     * @return PropertyMapping[]
     */
    public function getPropertiesMapping(MapperGeneratorMetadataInterface $mapperMetadata): array;

    /**
     * Extracts read accessor for a given source, target and property.
     */
    public function getReadAccessor(string $source, string $target, string $property): ?ReadAccessor;

    /**
     * Extracts write mutator for a given source, target and property.
     */
    public function getWriteMutator(string $source, string $target, string $property, array $context = []): ?WriteMutator;
}
