<?php

declare(strict_types=1);

namespace AutoMapper\Extractor;

use AutoMapper\Metadata\SourcePropertyMetadata;
use AutoMapper\Metadata\TargetPropertyMetadata;
use Symfony\Component\TypeInfo\Type;

/**
 * Extracts mapping between two objects, only gives properties that have the same name.
 *
 * @author Joel Wurtz <jwurtz@jolicode.com>
 *
 * @internal
 */
class SourceTargetMappingExtractor extends MappingExtractor
{
    /**
     * @param class-string $source
     * @param class-string $target
     */
    public function getTypes(string $source, SourcePropertyMetadata $sourceProperty, string $target, TargetPropertyMetadata $targetProperty, bool $extractTypesFromGetter): array
    {
        $sourceType = $this->sourceTypeExtractor->getType($source, $sourceProperty->property) ?? Type::mixed();
        $targetType = $this->targetTypeExtractor->getType($target, $targetProperty->property) ?? Type::mixed();

        return [$sourceType, $targetType];
    }
}
