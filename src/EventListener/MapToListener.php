<?php

declare(strict_types=1);

namespace AutoMapper\EventListener;

use AutoMapper\Attribute\MapFrom;
use AutoMapper\Attribute\MapTo;
use AutoMapper\Event\GenerateMapperEvent;
use AutoMapper\Event\PropertyMetadataEvent;
use AutoMapper\Event\SourcePropertyMetadata;
use AutoMapper\Event\TargetPropertyMetadata;
use AutoMapper\Exception\BadMapDefinitionException;
use AutoMapper\Transformer\CallableTransformer;
use AutoMapper\Transformer\CustomTransformer\CustomModelTransformer;
use AutoMapper\Transformer\CustomTransformer\CustomModelTransformerInterface;
use AutoMapper\Transformer\CustomTransformer\CustomPropertyTransformer;
use AutoMapper\Transformer\CustomTransformer\CustomPropertyTransformerInterface;
use AutoMapper\Transformer\CustomTransformer\CustomTransformersRegistry;
use AutoMapper\Transformer\TransformerInterface;

final readonly class MapToListener
{
    public function __construct(
        private CustomTransformersRegistry $customTransformersRegistry
    ) {
    }

    public function __invoke(GenerateMapperEvent $event): void
    {
        if ($event->mapperMetadata->sourceReflectionClass === null) {
            return;
        }

        $this->setPropertiesFromSource($event);
        $this->setPropertiesFromTarget($event);
    }

    private function setPropertiesFromSource(GenerateMapperEvent $event): void
    {
        if ($event->mapperMetadata->sourceReflectionClass === null) {
            return;
        }

        $properties = $event->mapperMetadata->sourceReflectionClass->getProperties();

        foreach ($properties as $reflectionProperty) {
            $mapToAttributes = $reflectionProperty->getAttributes(MapTo::class);

            if (0 === \count($mapToAttributes)) {
                continue;
            }

            foreach ($mapToAttributes as $mapToAttribute) {
                /** @var MapTo $mapToAttributeInstance */
                $mapToAttributeInstance = $mapToAttribute->newInstance();

                if ($mapToAttributeInstance->target !== null && $event->mapperMetadata->target !== $mapToAttributeInstance->target) {
                    continue;
                }

                $sourceProperty = new SourcePropertyMetadata($reflectionProperty->getName());
                $targetProperty = new TargetPropertyMetadata($mapToAttributeInstance->name ?? $reflectionProperty->getName());

                $property = new PropertyMetadataEvent(
                    $event->mapperMetadata,
                    $sourceProperty,
                    $targetProperty,
                    $mapToAttributeInstance->maxDepth,
                    $this->getTransformerFromMapAttribute($event->mapperMetadata->source, $mapToAttributeInstance),
                    $mapToAttributeInstance->ignore,
                );

                if (\array_key_exists($property->target->name, $event->properties)) {
                    throw new BadMapDefinitionException(sprintf('There is already a MapTo attribute with target "%s" in class "%s".', $property->target->name, $event->mapperMetadata->source));
                }

                $event->properties[$property->target->name] = $property;
            }
        }
    }

    private function setPropertiesFromTarget(GenerateMapperEvent $event): void
    {
        if ($event->mapperMetadata->targetReflectionClass === null) {
            return;
        }

        $properties = $event->mapperMetadata->targetReflectionClass->getProperties();

        foreach ($properties as $reflectionProperty) {
            $mapFromAttributes = $reflectionProperty->getAttributes(MapFrom::class);

            if (0 === \count($mapFromAttributes)) {
                continue;
            }

            foreach ($mapFromAttributes as $mapFromAttribute) {
                /** @var MapFrom $mapFromAttributeInstance */
                $mapFromAttributeInstance = $mapFromAttribute->newInstance();

                if ($mapFromAttributeInstance->source !== null && $event->mapperMetadata->source !== $mapFromAttributeInstance->source) {
                    continue;
                }

                $sourceProperty = new SourcePropertyMetadata($mapFromAttributeInstance->name ?? $reflectionProperty->getName());
                $targetProperty = new TargetPropertyMetadata($reflectionProperty->getName());

                $property = new PropertyMetadataEvent(
                    $event->mapperMetadata,
                    $sourceProperty,
                    $targetProperty,
                    $mapFromAttributeInstance->maxDepth,
                    $this->getTransformerFromMapAttribute($event->mapperMetadata->target, $mapFromAttributeInstance),
                    $mapFromAttributeInstance->ignore,
                );

                if (\array_key_exists($property->target->name, $event->properties)) {
                    throw new BadMapDefinitionException(sprintf('There is already a MapTo or MapFrom attribute with target "%s" in class "%s" or class "%s".', $property->target->name, $event->mapperMetadata->source, $event->mapperMetadata->target));
                }

                $event->properties[$property->target->name] = $property;
            }
        }
    }

    private function getTransformerFromMapAttribute(string $class, MapTo|MapFrom $attribute): ?TransformerInterface
    {
        $transformer = null;

        if ($attribute->transformer !== null) {
            $callableName = null;
            $transformerCallable = $attribute->transformer;

            if ($transformerCallable instanceof \Closure) {
                // This is not supported because we cannot generate code from a closure
                // However this should never be possible since attributes does not allow to pass a closure
                // Let's keep this check for future proof
                throw new BadMapDefinitionException('Closure transformer is not supported.');
            }

            if (\is_string($transformerCallable) && $customTransformer = $this->customTransformersRegistry->getCustomTransformer($transformerCallable)) {
                if ($customTransformer instanceof CustomModelTransformerInterface) {
                    $transformer = new CustomModelTransformer($transformerCallable);
                }

                if ($customTransformer instanceof CustomPropertyTransformerInterface) {
                    $transformer = new CustomPropertyTransformer($transformerCallable);
                }
            } elseif (\is_callable($transformerCallable, false, $callableName)) {
                $transformer = new CallableTransformer($callableName);
            } elseif (\is_string($transformerCallable)) {
                // Check the method exist on the class
                if (!method_exists($class, $transformerCallable)) {
                    if (class_exists($transformerCallable)) {
                        throw new BadMapDefinitionException(sprintf('Transformer "%s" targeted by %s transformer does not exist on class "%s", did you register it ?.', $transformerCallable, $attribute::class, $class));
                    }

                    throw new BadMapDefinitionException(sprintf('Method "%s" targeted by %s transformer does not exist on class "%s".', $transformerCallable, $attribute::class, $class));
                }

                $reflMethod = new \ReflectionMethod($class, $transformerCallable);

                if ($reflMethod->isStatic()) {
                    $transformer = new CallableTransformer($class . '::' . $transformerCallable);
                } else {
                    $transformer = new CallableTransformer($transformerCallable, true);
                }
            } else {
                throw new BadMapDefinitionException(sprintf('Callable "%s" targeted by %s transformer on class "%s" is not valid.', json_encode($transformerCallable), $attribute::class, $class));
            }
        }

        return $transformer;
    }
}
