<?php

declare(strict_types=1);

namespace AutoMapper\Extractor;

use AutoMapper\Metadata\SourcePropertyMetadata;
use AutoMapper\Metadata\TargetPropertyMetadata;
use AutoMapper\Metadata\TypesMatching;

/**
 * Extracts mapping between two objects, only gives properties that have the same name.
 *
 * @author Joel Wurtz <jwurtz@jolicode.com>
 *
 * @internal
 */
class SourceTargetMappingExtractor extends MappingExtractor
{
    public function getTypes(string $source, SourcePropertyMetadata $sourceProperty, string $target, TargetPropertyMetadata $targetProperty, bool $extractTypesFromGetter): TypesMatching
    {
        $sourceTypes = $this->propertyInfoExtractor->getTypes($source, $sourceProperty->property, [ReadWriteTypeExtractor::READ_ACCESSOR => $sourceProperty->accessor]) ?? [];
        $targetTypes = $this->propertyInfoExtractor->getTypes($target, $targetProperty->property, [ReadWriteTypeExtractor::WRITE_MUTATOR => $targetProperty->writeMutator, ReadWriteTypeExtractor::EXTRACT_TYPE_FROM_GETTER => $extractTypesFromGetter]) ?? [];

        return TypesMatching::fromSourceAndTargetTypes($sourceTypes, $targetTypes);
    }
}
