<?php

declare(strict_types=1);

namespace AutoMapper\Generator\Shared;

use AutoMapper\Metadata\GeneratorMetadata;
use AutoMapper\Metadata\PropertyMetadata;
use AutoMapper\Transformer\MapperDependency;

/**
 * @internal
 */
final readonly class ClassDiscriminatorResolver
{
    public function hasClassDiscriminator(GeneratorMetadata $metadata, bool $fromSource): bool
    {
        if ($fromSource) {
            return $metadata->sourceDiscriminator !== null;
        }

        return $metadata->targetDiscriminator !== null;
    }

    public function getDiscriminatorPropertyMetadata(GeneratorMetadata $metadata, bool $fromSource): ?PropertyMetadata
    {
        $discriminator = $fromSource ? $metadata->sourceDiscriminator : $metadata->targetDiscriminator;

        if (!$discriminator) {
            return null;
        }

        if ($discriminator->propertyName === null) {
            return null;
        }

        return array_find($metadata->propertiesMetadata,
            fn ($propertyMetadata,
            ) => ($fromSource ? $propertyMetadata->source->property : $propertyMetadata->target->property) === $discriminator->propertyName
        );
    }

    /**
     * @return list<MapperDependency>
     */
    public function getMappersList(GeneratorMetadata $metadata, bool $fromSource): array
    {
        $discriminator = $fromSource ? $metadata->sourceDiscriminator : $metadata->targetDiscriminator;

        if (!$discriminator) {
            return [];
        }

        $classList = array_values($discriminator->mapping);
        $typeList = array_keys($discriminator->mapping);
        $targetClassList = $discriminator->propertyName === null ? $typeList : array_fill(0, \count($classList), $fromSource ? $metadata->mapperMetadata->target : $metadata->mapperMetadata->source);
        $mappers = [];

        foreach ($classList as $index => $className) {
            /** @var class-string $sourceClass */
            $sourceClass = $fromSource ? $className : $targetClassList[$index];
            /** @var class-string $targetClass */
            $targetClass = $fromSource ? $targetClassList[$index] : $className;

            $mappers[] = new MapperDependency(
                name: "Discriminator_Mapper_{$sourceClass}_{$targetClass}",
                source: $sourceClass,
                target: $targetClass,
                type: $typeList[$index],
            );
        }

        return $mappers;
    }
}
