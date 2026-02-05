<?php

declare(strict_types=1);

namespace AutoMapper\EventListener;

use AutoMapper\Attribute\Mapper;
use AutoMapper\Event\GenerateMapperEvent;
use AutoMapper\Event\PropertyMetadataEvent;
use AutoMapper\Event\SourcePropertyMetadata;
use AutoMapper\Event\TargetPropertyMetadata;
use AutoMapper\Transformer\FixedValueTransformer;

/**
 * @internal
 */
final readonly class MapperListener
{
    public function __construct()
    {
    }

    public function __invoke(GenerateMapperEvent $event): void
    {
        // get mapper with highest priority
        [$directMapperAttribute, $fromSource, $isDirect] = $this->getMapperAttribute($event, false);

        if ($directMapperAttribute) {
            $event->checkAttributes ??= $directMapperAttribute->checkAttributes;
            $event->constructorStrategy ??= $directMapperAttribute->constructorStrategy;
            $event->allowReadOnlyTargetToPopulate ??= $directMapperAttribute->allowReadOnlyTargetToPopulate;
            $event->strictTypes ??= $directMapperAttribute->strictTypes;
            $event->allowExtraProperties ??= $directMapperAttribute->allowExtraProperties;
            $event->mapperMetadata->dateTimeFormat = $directMapperAttribute->dateTimeFormat;

            if ($directMapperAttribute->discriminator) {
                if ($fromSource) {
                    $event->sourceDiscriminator = $directMapperAttribute->discriminator;
                } else {
                    $event->targetDiscriminator = $directMapperAttribute->discriminator;

                    if ($directMapperAttribute->discriminator->propertyName) {
                        $property = $directMapperAttribute->discriminator->propertyName;
                        $sourceProperty = new SourcePropertyMetadata($property);
                        $targetProperty = new TargetPropertyMetadata($property);

                        $event->properties[$property] = new PropertyMetadataEvent(
                            mapperMetadata: $event->mapperMetadata,
                            source: $sourceProperty,
                            target: $targetProperty,
                        );
                    }
                }
            }
        }

        // get discriminator from mapper if not already set, which should also check parent class
        [$attributeForDiscriminator, $fromSource, $isDirect] = $this->getMapperAttribute($event, true);

        if (null === $attributeForDiscriminator || null === $attributeForDiscriminator->discriminator || null === $attributeForDiscriminator->discriminator->propertyName) {
            return;
        }

        // In this case we have a propertyName and not a direct discriminator let's add the property transformer for it
        if ($fromSource && !$isDirect) {
            foreach ($attributeForDiscriminator->discriminator->mapping as $type => $class) {
                if ($class === $event->mapperMetadata->source) {
                    $property = $attributeForDiscriminator->discriminator->propertyName;
                    $sourceProperty = new SourcePropertyMetadata($property);
                    $targetProperty = new TargetPropertyMetadata($property);

                    $event->properties[$property] = new PropertyMetadataEvent(
                        mapperMetadata: $event->mapperMetadata,
                        source: $sourceProperty,
                        target: $targetProperty,
                        transformer: new FixedValueTransformer($type),
                    );
                }
            }
        }
    }

    /**
     * @return array{0: ?Mapper, 1: bool, 2: bool}
     */
    private function getMapperAttribute(GenerateMapperEvent $event, bool $allowParent): array
    {
        /** @var array{0: Mapper, 1: bool, 2: bool}[] $mappers */
        $mappers = [];

        if ($event->mapperMetadata->sourceReflectionClass) {
            foreach ($this->getMappers($event->mapperMetadata->sourceReflectionClass, $event->mapperMetadata->target, true, $allowParent) as [$mapper, $isDirect]) {
                $mappers[] = [$mapper, true, $isDirect];
            }
        }

        if ($event->mapperMetadata->targetReflectionClass) {
            foreach ($this->getMappers($event->mapperMetadata->targetReflectionClass, $event->mapperMetadata->source, false, $allowParent) as [$mapper, $isDirect]) {
                $mappers[] = [$mapper, false, $isDirect];
            }
        }

        if (0 === \count($mappers)) {
            return [null, false, false];
        }

        // sort by priority
        usort($mappers, fn (array $a, array $b) => $a[0]->priority <=> $b[0]->priority);

        return $mappers[0];
    }

    /**
     * @param \ReflectionClass<object> $reflectionClass
     *
     * @return \Generator<array{0: Mapper, 1: bool}>
     */
    private function getMappers(\ReflectionClass $reflectionClass, string $targetOrSource, bool $fromSource, bool $allowParent = false, bool $isDirect = true): \Generator
    {
        $attributes = $reflectionClass->getAttributes(Mapper::class);

        foreach ($attributes as $attribute) {
            $mapper = $attribute->newInstance();

            if ($fromSource && $mapper->target === null) {
                yield [$mapper, $isDirect];
            }

            if (!$fromSource && $mapper->source === null) {
                yield [$mapper, $isDirect];
            }

            if ($fromSource && \is_string($mapper->target) && $mapper->target === $targetOrSource) {
                yield [$mapper, $isDirect];
            }

            if (!$fromSource && \is_string($mapper->source) && $mapper->source === $targetOrSource) {
                yield [$mapper, $isDirect];
            }

            if ($fromSource && \is_array($mapper->target) && \in_array($targetOrSource, $mapper->target, true)) {
                yield [$mapper, $isDirect];
            }

            if (!$fromSource && \is_array($mapper->source) && \in_array($targetOrSource, $mapper->source, true)) {
                yield [$mapper, $isDirect];
            }
        }

        if (!$allowParent) {
            return;
        }

        // Include metadata from the parent class
        if ($parent = $reflectionClass->getParentClass()) {
            yield from $this->getMappers($parent, $targetOrSource, $fromSource, true, false);
        }

        // Include metadata from all implemented interfaces
        foreach ($reflectionClass->getInterfaces() as $interface) {
            yield from $this->getMappers($interface, $targetOrSource, $fromSource, true, false);
        }
    }
}
