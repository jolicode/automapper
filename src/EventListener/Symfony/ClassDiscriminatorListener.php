<?php

declare(strict_types=1);

namespace AutoMapper\EventListener\Symfony;

use AutoMapper\Event\GenerateMapperEvent;
use AutoMapper\Event\PropertyMetadataEvent;
use AutoMapper\Event\SourcePropertyMetadata;
use AutoMapper\Event\TargetPropertyMetadata;
use AutoMapper\Metadata\Discriminator;
use AutoMapper\Transformer\FixedValueTransformer;
use Symfony\Component\Serializer\Mapping\ClassDiscriminatorMapping;
use Symfony\Component\Serializer\Mapping\ClassDiscriminatorResolverInterface;

/**
 * @internal
 */
final readonly class ClassDiscriminatorListener
{
    public function __construct(
        private ClassDiscriminatorResolverInterface $classDiscriminator,
    ) {
    }

    public function __invoke(GenerateMapperEvent $event): void
    {
        $classDiscriminatorMappingSource = $this->getMappingForClass($event->mapperMetadata->source);
        $classDiscriminatorMappingTarget = $this->getMappingForClass($event->mapperMetadata->target);

        if ($classDiscriminatorMappingSource) {
            $sourceType = null;

            foreach ($classDiscriminatorMappingSource->getTypesMapping() as $type => $class) {
                if ($class === $event->mapperMetadata->source) {
                    $sourceType = $type;
                    break;
                }
            }

            if (null === $sourceType) {
                // It means the source is a parent class or interface of the mapped types
                $event->sourceDiscriminator = new Discriminator(
                    mapping: $classDiscriminatorMappingSource->getTypesMapping(),
                    propertyName: $classDiscriminatorMappingSource->getTypeProperty(),
                );
            }

            $property = $classDiscriminatorMappingSource->getTypeProperty();
            $sourceProperty = new SourcePropertyMetadata($property);
            $targetProperty = new TargetPropertyMetadata($property);

            $event->properties[$property] = new PropertyMetadataEvent(
                mapperMetadata: $event->mapperMetadata,
                source: $sourceProperty,
                target: $targetProperty,
                transformer: $sourceType ? new FixedValueTransformer($sourceType) : null,
            );
        }

        if ($classDiscriminatorMappingTarget) {
            $targetType = null;

            foreach ($classDiscriminatorMappingTarget->getTypesMapping() as $type => $class) {
                if ($class === $event->mapperMetadata->target) {
                    $targetType = $type;
                    break;
                }
            }

            if (null === $targetType) {
                // It means the target is a parent class or interface of the mapped types
                $event->targetDiscriminator = new Discriminator(
                    mapping: $classDiscriminatorMappingTarget->getTypesMapping(),
                    propertyName: $classDiscriminatorMappingTarget->getTypeProperty(),
                );
            }

            $property = $classDiscriminatorMappingTarget->getTypeProperty();
            $sourceProperty = new SourcePropertyMetadata($property);
            $targetProperty = new TargetPropertyMetadata($property);

            $event->properties[$property] = new PropertyMetadataEvent(
                mapperMetadata: $event->mapperMetadata,
                source: $sourceProperty,
                target: $targetProperty,
            );
        }
    }

    private function getMappingForClass(string $class): ?ClassDiscriminatorMapping
    {
        if (!class_exists($class) && !interface_exists($class)) {
            return null;
        }

        $mapping = $this->classDiscriminator->getMappingForClass($class);

        if ($mapping) {
            return $mapping;
        }

        $reflectionClass = new \ReflectionClass($class);

        // Include metadata from the parent class
        if ($parent = $reflectionClass->getParentClass()) {
            $mapping = $this->getMappingForClass($parent->name);

            if ($mapping) {
                return $mapping;
            }
        }

        // Include metadata from all implemented interfaces
        foreach ($reflectionClass->getInterfaces() as $interface) {
            if ($mapping = $this->getMappingForClass($interface->name)) {
                return $mapping;
            }
        }

        return null;
    }
}
