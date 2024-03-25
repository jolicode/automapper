<?php

declare(strict_types=1);

namespace AutoMapper\Generator\Shared;

use AutoMapper\Metadata\GeneratorMetadata;
use AutoMapper\Metadata\PropertyMetadata;
use AutoMapper\Transformer\TransformerInterface;
use Symfony\Component\Serializer\Mapping\ClassDiscriminatorMapping;
use Symfony\Component\Serializer\Mapping\ClassDiscriminatorResolverInterface;

/**
 * @internal
 */
final readonly class ClassDiscriminatorResolver
{
    public function __construct(
        private ?ClassDiscriminatorResolverInterface $classDiscriminator = null,
    ) {
    }

    public function hasClassDiscriminator(GeneratorMetadata $metadata, bool $fromSource): bool
    {
        if (!($fromSource ? $metadata->isSourceUserDefined() : $metadata->isTargetUserDefined())
            || !($propertyMetadata = $this->getDiscriminatorPropertyMetadata($metadata, $fromSource))
            || !$propertyMetadata->transformer instanceof TransformerInterface
        ) {
            return false;
        }

        return true;
    }

    public function getDiscriminatorPropertyMetadata(GeneratorMetadata $metadata, bool $fromSource): ?PropertyMetadata
    {
        $classDiscriminatorMapping = $this->classDiscriminator?->getMappingForClass($fromSource ? $metadata->mapperMetadata->source : $metadata->mapperMetadata->target);

        if (!$classDiscriminatorMapping) {
            return null;
        }

        foreach ($metadata->propertiesMetadata as $propertyMetadata) {
            if (($fromSource ? $propertyMetadata->source->name : $propertyMetadata->target->name) === $classDiscriminatorMapping->getTypeProperty()) {
                return $propertyMetadata;
            }
        }

        return null;
    }

    /**
     * @return array<string, string>
     */
    public function discriminatorMapperNames(GeneratorMetadata $metadata, bool $fromSource): array
    {
        $classDiscriminatorMapping = $this->classDiscriminator?->getMappingForClass($fromSource ? $metadata->mapperMetadata->source : $metadata->mapperMetadata->target);

        if (!$classDiscriminatorMapping) {
            return [];
        }

        return array_combine(
            array_values($classDiscriminatorMapping->getTypesMapping()),
            $this->discriminatorNames($metadata, $classDiscriminatorMapping, $fromSource)
        );
    }

    /**
     * @return array<string, string>
     */
    public function discriminatorMapperNamesIndexedByTypeValue(GeneratorMetadata $metadata, bool $fromSource): array
    {
        $classDiscriminatorMapping = $this->classDiscriminator?->getMappingForClass($fromSource ? $metadata->mapperMetadata->source : $metadata->mapperMetadata->target);

        if (!$classDiscriminatorMapping) {
            return [];
        }

        return array_combine(
            array_keys($classDiscriminatorMapping->getTypesMapping()),
            $this->discriminatorNames($metadata, $classDiscriminatorMapping, $fromSource)
        );
    }

    /**
     * @return list<string>
     */
    private function discriminatorNames(GeneratorMetadata $metadata, ClassDiscriminatorMapping $classDiscriminatorMapping, bool $fromSource): array
    {
        return array_map(
            static fn (string $typeTarget) => $fromSource ? "Discriminator_Mapper_{$typeTarget}_{$metadata->mapperMetadata->target}" : "Discriminator_Mapper_{$metadata->mapperMetadata->source}_{$typeTarget}",
            $classDiscriminatorMapping->getTypesMapping()
        );
    }
}
