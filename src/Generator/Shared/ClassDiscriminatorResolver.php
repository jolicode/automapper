<?php

declare(strict_types=1);

namespace AutoMapper\Generator\Shared;

use AutoMapper\Extractor\PropertyMapping;
use AutoMapper\MapperGeneratorMetadataInterface;
use AutoMapper\Transformer\TransformerInterface;
use Symfony\Component\Serializer\Mapping\ClassDiscriminatorMapping;
use Symfony\Component\Serializer\Mapping\ClassDiscriminatorResolverInterface;

/**
 * @internal
 */
final readonly class ClassDiscriminatorResolver
{
    public function __construct(
        private ClassDiscriminatorResolverInterface|null $classDiscriminator = null,
    ) {
    }

    public function hasClassDiscriminator(MapperGeneratorMetadataInterface $mapperMetadata): bool
    {
        if (!$mapperMetadata->targetIsAUserDefinedClass()
            || !($propertyMapping = $this->propertyMapping($mapperMetadata))
            || !$propertyMapping->transformer instanceof TransformerInterface
            || $propertyMapping->hasCustomTransformer()
        ) {
            return false;
        }

        return true;
    }

    public function propertyMapping(MapperGeneratorMetadataInterface $mapperMetadata): PropertyMapping|null
    {
        $classDiscriminatorMapping = $this->classDiscriminator?->getMappingForClass($mapperMetadata->getTarget());

        if (!$classDiscriminatorMapping) {
            return null;
        }

        return $mapperMetadata->getPropertyMapping($classDiscriminatorMapping->getTypeProperty());
    }

    /**
     * @return array<string, string>
     */
    public function discriminatorMapperNames(MapperGeneratorMetadataInterface $mapperMetadata): array
    {
        $classDiscriminatorMapping = $this->classDiscriminator?->getMappingForClass($mapperMetadata->getTarget());

        if (!$classDiscriminatorMapping) {
            return [];
        }

        return array_combine(
            array_values($classDiscriminatorMapping->getTypesMapping()),
            $this->discriminatorNames($mapperMetadata, $classDiscriminatorMapping)
        );
    }

    /**
     * @return array<string, string>
     */
    public function discriminatorMapperNamesIndexedByTypeValue(MapperGeneratorMetadataInterface $mapperMetadata): array
    {
        $classDiscriminatorMapping = $this->classDiscriminator?->getMappingForClass($mapperMetadata->getTarget());

        if (!$classDiscriminatorMapping) {
            return [];
        }

        return array_combine(
            array_keys($classDiscriminatorMapping->getTypesMapping()),
            $this->discriminatorNames($mapperMetadata, $classDiscriminatorMapping)
        );
    }

    /**
     * @return list<string>
     */
    private function discriminatorNames(MapperGeneratorMetadataInterface $mapperMetadata, ClassDiscriminatorMapping $classDiscriminatorMapping): array
    {
        return array_map(
            static fn (string $typeTarget) => "Discriminator_Mapper_{$mapperMetadata->getSource()}_{$typeTarget}",
            $classDiscriminatorMapping->getTypesMapping()
        );
    }
}
