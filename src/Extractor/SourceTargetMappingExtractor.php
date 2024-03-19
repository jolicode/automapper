<?php

declare(strict_types=1);

namespace AutoMapper\Extractor;

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
    public function getTypes(string $source, string $sourceProperty, string $target, string $targetProperty): TypesMatching
    {
        $sourceTypes = $this->propertyInfoExtractor->getTypes($source, $sourceProperty) ?? [];
        $targetTypes = $this->propertyInfoExtractor->getTypes($target, $targetProperty) ?? [];

        return TypesMatching::fromSourceAndTargetTypes($sourceTypes, $targetTypes);
    }
}
