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
final readonly class ClassDiscriminatorResolver implements ClassDiscriminatorResolverInterface
{
    /**
     * @param array<class-string, ClassDiscriminatorMapping> $mappings
     */
    public function __construct(
        private ?ClassDiscriminatorResolverInterface $classDiscriminator = null,
        private array $mappings = [],
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
        $classDiscriminatorMapping = $this->getMappingForClass($fromSource ? $metadata->mapperMetadata->source : $metadata->mapperMetadata->target);

        if (!$classDiscriminatorMapping) {
            return null;
        }

        foreach ($metadata->propertiesMetadata as $propertyMetadata) {
            if (($fromSource ? $propertyMetadata->source->property : $propertyMetadata->target->property) === $classDiscriminatorMapping->getTypeProperty()) {
                return $propertyMetadata;
            }
        }

        return null;
    }

    /**
     * @return array<class-string<object>, string>
     */
    public function discriminatorMapperNames(GeneratorMetadata $metadata, bool $fromSource): array
    {
        $classDiscriminatorMapping = $this->getMappingForClass($fromSource ? $metadata->mapperMetadata->source : $metadata->mapperMetadata->target);

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
        $classDiscriminatorMapping = $this->getMappingForClass($fromSource ? $metadata->mapperMetadata->source : $metadata->mapperMetadata->target);

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

    public function getMappingForClass(string $class): null|ClassDiscriminatorMapping
    {
        if (\array_key_exists($class, $this->mappings)) {
            return $this->mappings[$class];
        }

        return $this->classDiscriminator?->getMappingForClass($class);
    }

    public function getMappingForMappedObject(object|string $object): null|ClassDiscriminatorMapping
    {
        foreach ($this->mappings as $baseClass => $mapping) {
            if ($object instanceof $baseClass) {
                return $mapping;
            }
        }

        return $this->classDiscriminator?->getMappingForMappedObject($object);
    }

    public function getTypeForMappedObject(object|string $object): ?string
    {
        foreach ($this->mappings as $mapping) {
            foreach ($mapping->getTypesMapping() as $type => $className) {
                if ($object instanceof $className) {
                    return $type;
                }
            }
        }

        return $this->classDiscriminator?->getTypeForMappedObject($object);
    }
}
