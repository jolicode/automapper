<?php

declare(strict_types=1);

namespace AutoMapper\MapperMetadata;

use AutoMapper\Extractor\PropertyMapping;
use AutoMapper\Transformer\MapperDependency;

/**
 * Stores metadata needed for mapping data.
 *
 * @author Joel Wurtz <jwurtz@jolicode.com>
 */
interface MapperMetadataInterface
{
    /**
     * Get the source type mapped.
     */
    public function getSource(): string;

    /**
     * Get the target type mapped.
     */
    public function getTarget(): string;

    /**
     * Returns true if target is an object.
     */
    public function targetIsAUserDefinedClass(): bool;

    /**
     * Check if the target is a read-only class.
     */
    public function isTargetReadOnlyClass(): bool;

    /**
     * Get a set of all dependencies, deduplicated.
     *
     * @return list<MapperDependency>
     */
    public function getAllDependencies(): array;

    /**
     * Get properties to map between source and target.
     *
     * @return PropertyMapping[]
     */
    public function getPropertiesMapping(): array;

    /**
     * Get propertyMapping by property name, or null if not mapped.
     */
    public function getPropertyMapping(string $property): ?PropertyMapping;

    /**
     * Get date time format to use when mapping date time to string.
     */
    public function getDateTimeFormat(): string;
}
