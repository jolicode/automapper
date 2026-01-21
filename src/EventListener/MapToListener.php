<?php

declare(strict_types=1);

namespace AutoMapper\EventListener;

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
final readonly class MapToListener extends MapListener
{
    public function __invoke(GenerateMapperEvent $event): void
    {
        if ($event->mapperMetadata->sourceReflectionClass === null) {
            return;
        }

        $properties = $event->mapperMetadata->sourceReflectionClass->getProperties();
        $methods = $event->mapperMetadata->sourceReflectionClass->getMethods();

        foreach ([$event->mapperMetadata->sourceReflectionClass, ...$properties, ...$methods] as $reflectionClassOrPropertyOrMethod) {
            $mapToAttributes = $reflectionClassOrPropertyOrMethod->getAttributes(MapTo::class);

            if (0 === \count($mapToAttributes)) {
                continue;
            }

            foreach ($mapToAttributes as $index => $mapToAttribute) {
                /** @var MapTo $mapToAttributeInstance */
                $mapToAttributeInstance = $mapToAttribute->newInstance();

                if ($reflectionClassOrPropertyOrMethod instanceof \ReflectionClass) {
                    if ($mapToAttributeInstance->property === null) {
                        throw new BadMapDefinitionException(\sprintf('Required `property` property in the "%s" attribute on "%s" class.', MapTo::class, $reflectionClassOrPropertyOrMethod->getName()));
                    }

                    if ($mapToAttributeInstance->transformer === null) {
                        throw new BadMapDefinitionException(\sprintf('Required `transformer` property in the "%s" attribute on "%s" class.', MapTo::class, $reflectionClassOrPropertyOrMethod->getName()));
                    }

                    $property = $mapToAttributeInstance->property;
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

                $this->addPropertyFromSource($event, $mapToAttributeInstance, $property, $reference);
            }
        }
    }

    private function addPropertyFromSource(GenerateMapperEvent $event, MapTo $mapTo, string $property, Reference $reference): void
    {
        $targets = null === $mapTo->target ? null : (\is_array($mapTo->target) ? $mapTo->target : [$mapTo->target]);

        if ($targets !== null && !\in_array($event->mapperMetadata->target, $targets, true)) {
            return;
        }

        $sourcePropertyType = \is_string($mapTo->sourcePropertyType) ? $this->stringTypeResolver->resolve($mapTo->sourcePropertyType) : $mapTo->sourcePropertyType;
        $targetPropertyType = \is_string($mapTo->targetPropertyType) ? $this->stringTypeResolver->resolve($mapTo->targetPropertyType) : $mapTo->targetPropertyType;

        $sourceProperty = new SourcePropertyMetadata($property, type: $sourcePropertyType);
        $targetProperty = new TargetPropertyMetadata($mapTo->property ?? $property, type: $targetPropertyType);

        $propertyMetadata = new PropertyMetadataEvent(
            mapperMetadata: $event->mapperMetadata,
            source: $sourceProperty,
            target: $targetProperty,
            maxDepth: $mapTo->maxDepth,
            transformer: $this->getTransformerFromMapAttribute($event->mapperMetadata->source, $mapTo, $reference),
            dateTimeFormat: $mapTo->dateTimeFormat,
            ignored: $mapTo->ignore,
            ignoreReason: $mapTo->ignore === true ? 'Property is ignored by MapTo Attribute on Source' : null,
            if: $mapTo->if,
            groups: $mapTo->groups,
            priority: $mapTo->priority,
            extractTypesFromGetter: $mapTo->extractTypesFromGetter,
            identifier: $mapTo->identifier,
        );

        if (\array_key_exists($propertyMetadata->target->property, $event->properties) && $event->properties[$propertyMetadata->target->property]->priority >= $propertyMetadata->priority) {
            return;
        }

        $event->properties[$propertyMetadata->target->property] = $propertyMetadata;
    }
}
