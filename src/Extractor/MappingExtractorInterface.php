<?php

declare(strict_types=1);

namespace AutoMapper\Extractor;

use AutoMapper\Metadata\SourcePropertyMetadata;
use AutoMapper\Metadata\TargetPropertyMetadata;
use AutoMapper\Metadata\TypesMatching;

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
     * @return list<string>
     */
    public function getProperties(string $class): iterable;

    public function getTypes(string $source, SourcePropertyMetadata $sourceProperty, string $target, TargetPropertyMetadata $targetProperty): TypesMatching;

    public function getDateTimeFormat(string $class, string $property): string;

    /**
     * @return list<string>|null
     */
    public function getGroups(string $class, string $property): ?array;

    public function getCheckExists(string $class, string $property): bool;

    /**
     * Extracts read accessor for a given source, target and property.
     */
    public function getReadAccessor(string $source, string $target, string $property): ?ReadAccessor;

    /**
     * Extracts write mutator for a given source, target and property.
     *
     * @param array<string, mixed> $context
     */
    public function getWriteMutator(string $source, string $target, string $property, array $context = []): ?WriteMutator;
}
