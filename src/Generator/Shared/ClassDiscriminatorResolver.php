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

    public function hasClassDiscriminator(GeneratorMetadata $metadata): bool
    {
        if (!$metadata->isTargetUserDefined()
            || !($propertyMetadata = $this->getDiscriminatorPropertyMetadata($metadata))
            || !$propertyMetadata->transformer instanceof TransformerInterface
        ) {
            return false;
        }

        return true;
    }

    public function getDiscriminatorPropertyMetadata(GeneratorMetadata $metadata): ?PropertyMetadata
    {
        $classDiscriminatorMapping = $this->classDiscriminator?->getMappingForClass($metadata->mapperMetadata->target);

        if (!$classDiscriminatorMapping) {
            return null;
        }

        foreach ($metadata->propertiesMetadata as $propertyMetadata) {
            if ($propertyMetadata->target->name === $classDiscriminatorMapping->getTypeProperty()) {
                return $propertyMetadata;
            }
        }

        return null;
    }

    /**
     * @return array<string, string>
     */
    public function discriminatorMapperNames(GeneratorMetadata $metadata): array
    {
        $classDiscriminatorMapping = $this->classDiscriminator?->getMappingForClass($metadata->mapperMetadata->target);

        if (!$classDiscriminatorMapping) {
            return [];
        }

        return array_combine(
            array_values($classDiscriminatorMapping->getTypesMapping()),
            $this->discriminatorNames($metadata, $classDiscriminatorMapping)
        );
    }

    /**
     * @return array<string, string>
     */
    public function discriminatorMapperNamesIndexedByTypeValue(GeneratorMetadata $metadata): array
    {
        $classDiscriminatorMapping = $this->classDiscriminator?->getMappingForClass($metadata->mapperMetadata->target);

        if (!$classDiscriminatorMapping) {
            return [];
        }

        return array_combine(
            array_keys($classDiscriminatorMapping->getTypesMapping()),
            $this->discriminatorNames($metadata, $classDiscriminatorMapping)
        );
    }

    /**
     * @return list<string>
     */
    private function discriminatorNames(GeneratorMetadata $metadata, ClassDiscriminatorMapping $classDiscriminatorMapping): array
    {
        return array_map(
            static fn (string $typeTarget) => "Discriminator_Mapper_{$metadata->mapperMetadata->source}_{$typeTarget}",
            $classDiscriminatorMapping->getTypesMapping()
        );
    }
}
