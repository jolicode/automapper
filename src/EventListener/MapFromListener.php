<?php

declare(strict_types=1);

namespace AutoMapper\EventListener;

use AutoMapper\Attribute\MapFrom;
use AutoMapper\Attribute\MapTo;
use AutoMapper\AttributeReference\Reference;
use AutoMapper\Event\GenerateMapperEvent;
use AutoMapper\Event\PropertyMetadataEvent;
use AutoMapper\Event\SourcePropertyMetadata;
use AutoMapper\Event\TargetPropertyMetadata;
use AutoMapper\Exception\BadMapDefinitionException;

/**
 * @internal
 */
final readonly class MapFromListener extends MapListener
{
    public function __invoke(GenerateMapperEvent $event): void
    {
        if ($event->mapperMetadata->targetReflectionClass === null) {
            return;
        }

        $properties = $event->mapperMetadata->targetReflectionClass->getProperties();
        $methods = $event->mapperMetadata->targetReflectionClass->getMethods();

        foreach ([$event->mapperMetadata->targetReflectionClass, ...$properties, ...$methods] as $reflectionClassOrPropertyOrMethod) {
            $mapFromAttributes = $reflectionClassOrPropertyOrMethod->getAttributes(MapFrom::class);

            if (0 === \count($mapFromAttributes)) {
                continue;
            }

            foreach ($mapFromAttributes as $index => $mapFromAttribute) {
                /** @var MapFrom $mapFromAttributeInstance */
                $mapFromAttributeInstance = $mapFromAttribute->newInstance();

                if ($reflectionClassOrPropertyOrMethod instanceof \ReflectionClass) {
                    if ($mapFromAttributeInstance->property === null) {
                        throw new BadMapDefinitionException(\sprintf('Required `property` property in the "%s" attribute on "%s" class.', MapFrom::class, $reflectionClassOrPropertyOrMethod->getName()));
                    }

                    if ($mapFromAttributeInstance->transformer === null) {
                        throw new BadMapDefinitionException(\sprintf('Required `transformer` property in the "%s" attribute on "%s" class.', MapFrom::class, $reflectionClassOrPropertyOrMethod->getName()));
                    }

                    $property = $mapFromAttributeInstance->property;
                    $reference = new Reference(MapTo::class, $index, $reflectionClassOrPropertyOrMethod->getName());
                } elseif ($reflectionClassOrPropertyOrMethod instanceof \ReflectionMethod) {
                    $property = $this->getPropertyName($reflectionClassOrPropertyOrMethod->getName(), $properties);
                    $reference = new Reference(MapTo::class, $index, $reflectionClassOrPropertyOrMethod->getDeclaringClass()->getName(), methodName: $reflectionClassOrPropertyOrMethod->getName());
                } else {
                    $property = $reflectionClassOrPropertyOrMethod->getName();
                    $reference = new Reference(MapTo::class, $index, $reflectionClassOrPropertyOrMethod->getDeclaringClass()->getName(), propertyName: $reflectionClassOrPropertyOrMethod->getName());
                }

                if (null === $property) {
                    $property = $reflectionClassOrPropertyOrMethod->getName();
                }

                $this->addPropertyFromTarget($event, $mapFromAttributeInstance, $property, $reference);
            }
        }
    }

    private function addPropertyFromTarget(GenerateMapperEvent $event, MapFrom $mapFrom, string $property, Reference $reference): void
    {
        $sources = null === $mapFrom->source ? null : (\is_array($mapFrom->source) ? $mapFrom->source : [$mapFrom->source]);

        if ($sources !== null && !\in_array($event->mapperMetadata->source, $sources, true)) {
            return;
        }

        $sourcePropertyType = \is_string($mapFrom->sourcePropertyType) ? $this->stringTypeResolver->resolve($mapFrom->sourcePropertyType) : $mapFrom->sourcePropertyType;
        $targetPropertyType = \is_string($mapFrom->targetPropertyType) ? $this->stringTypeResolver->resolve($mapFrom->targetPropertyType) : $mapFrom->targetPropertyType;

        $sourceProperty = new SourcePropertyMetadata($mapFrom->property ?? $property, type: $sourcePropertyType);
        $targetProperty = new TargetPropertyMetadata($property, type: $targetPropertyType);

        $propertyMetadata = new PropertyMetadataEvent(
            mapperMetadata: $event->mapperMetadata,
            source: $sourceProperty,
            target: $targetProperty,
            maxDepth: $mapFrom->maxDepth,
            transformer: $this->getTransformerFromMapAttribute($event->mapperMetadata->target, $mapFrom, $reference, false),
            dateTimeFormat: $mapFrom->dateTimeFormat,
            ignored: $mapFrom->ignore,
            ignoreReason: $mapFrom->ignore === true ? 'Property is ignored by MapFrom Attribute on Target' : null,
            if: $mapFrom->if,
            groups: $mapFrom->groups,
            priority: $mapFrom->priority,
            extractTypesFromGetter: $mapFrom->extractTypesFromGetter,
            identifier: $mapFrom->identifier,
        );

        if (\array_key_exists($propertyMetadata->target->property, $event->properties) && $event->properties[$propertyMetadata->target->property]->priority >= $propertyMetadata->priority) {
            return;
        }

        $event->properties[$propertyMetadata->target->property] = $propertyMetadata;
    }
}
