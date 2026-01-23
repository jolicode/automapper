<?php

declare(strict_types=1);

namespace AutoMapper\Extractor;

use AutoMapper\Event\PropertyMetadataEvent;
use AutoMapper\Metadata\SourcePropertyMetadata;
use AutoMapper\Metadata\TargetPropertyMetadata;
use Symfony\Component\TypeInfo\Type;

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
     * @param class-string|'array' $class
     *
     * @return list<string>
     */
    public function getProperties(string $class): iterable;

    /**
     * @param class-string|'array' $source
     * @param class-string|'array' $target
     *
     * @return array{Type, Type}
     */
    public function getTypes(string $source, SourcePropertyMetadata $sourceProperty, string $target, TargetPropertyMetadata $targetProperty, bool $extractTypesFromGetter): array;

    public function getDateTimeFormat(PropertyMetadataEvent $propertyMetadataEvent): string;

    /**
     * @return list<string>|null
     */
    public function getGroups(string $class, string $property): ?array;

    public function getCheckExists(string $class, string $property): bool;

    /**
     * Extracts read accessor for a given source, target and property.
     */
    public function getReadAccessor(string $class, string $property): ?ReadAccessorInterface;

    /**
     * Extracts write mutator for a given source, target and property.
     *
     * @param array<string, mixed> $context
     */
    public function getWriteMutator(string $source, string $target, string $property, array $context = []): ?WriteMutatorInterface;
}
