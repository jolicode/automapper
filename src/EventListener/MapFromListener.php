<?php

declare(strict_types=1);

namespace AutoMapper\EventListener;

use AutoMapper\Attribute\MapFrom;
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

        foreach ([...$properties, ...$methods] as $reflectionPropertyOrMethod) {
            $mapFromAttributes = $reflectionPropertyOrMethod->getAttributes(MapFrom::class);

            if (0 === \count($mapFromAttributes)) {
                continue;
            }

            foreach ($mapFromAttributes as $mapFromAttribute) {
                /** @var MapFrom $mapFromAttributeInstance */
                $mapFromAttributeInstance = $mapFromAttribute->newInstance();
                $name = $reflectionPropertyOrMethod instanceof \ReflectionMethod ? $this->getPropertyName($reflectionPropertyOrMethod->getName(), $properties) : $reflectionPropertyOrMethod->getName();

                if (null === $name) {
                    $name = $reflectionPropertyOrMethod->getName();
                }

                $this->addPropertyFromTarget($event, $mapFromAttributeInstance, $name);
            }
        }
    }

    private function addPropertyFromTarget(GenerateMapperEvent $event, MapFrom $mapFrom, string $name): void
    {
        if ($mapFrom->source !== null && $event->mapperMetadata->source !== $mapFrom->source) {
            return;
        }

        $sourceProperty = new SourcePropertyMetadata($mapFrom->name ?? $name);
        $targetProperty = new TargetPropertyMetadata($name);

        $property = new PropertyMetadataEvent(
            $event->mapperMetadata,
            $sourceProperty,
            $targetProperty,
            $mapFrom->maxDepth,
            $this->getTransformerFromMapAttribute($event->mapperMetadata->target, $mapFrom),
            $mapFrom->ignore,
        );

        if (\array_key_exists($property->target->name, $event->properties)) {
            throw new BadMapDefinitionException(sprintf('There is already a MapTo or MapFrom attribute with target "%s" in class "%s" or class "%s".', $property->target->name, $event->mapperMetadata->source, $event->mapperMetadata->target));
        }

        $event->properties[$property->target->name] = $property;
    }
}
