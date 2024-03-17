<?php

declare(strict_types=1);

namespace AutoMapper\EventListener;

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

                $transformer = null;

                if ($mapToAttributeInstance->transformer !== null) {
                    $callableName = null;
                    $transformerCallable = $mapToAttributeInstance->transformer;

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
                        if (!method_exists($event->mapperMetadata->source, $transformerCallable)) {
                            if (class_exists($transformerCallable)) {
                                throw new BadMapDefinitionException(sprintf('Transformer "%s" targeted by MapTo transformer does not exist on class "%s", did you register it ?.', $transformerCallable, $event->mapperMetadata->source));
                            }

                            throw new BadMapDefinitionException(sprintf('Method "%s" targeted by MapTo transformer does not exist on class "%s".', $transformerCallable, $event->mapperMetadata->source));
                        }

                        $reflMethod = new \ReflectionMethod($event->mapperMetadata->source, $transformerCallable);

                        if ($reflMethod->isStatic()) {
                            $transformer = new CallableTransformer($event->mapperMetadata->source . '::' . $transformerCallable);
                        } else {
                            $transformer = new CallableTransformer($transformerCallable, true);
                        }
                    } else {
                        throw new BadMapDefinitionException(sprintf('Callable "%s" targeted by MapTo transformer on class "%s" is not valid.', json_encode($transformerCallable), $event->mapperMetadata->source));
                    }
                }

                $property = new PropertyMetadataEvent(
                    $event->mapperMetadata,
                    $sourceProperty,
                    $targetProperty,
                    $mapToAttributeInstance->maxDepth,
                    $transformer,
                    $mapToAttributeInstance->ignore,
                );

                if (\array_key_exists($property->target->name, $event->properties)) {
                    throw new BadMapDefinitionException(sprintf('There is already a MapTo attribute with target "%s" in class "%s".', $property->target->name, $event->mapperMetadata->source));
                }

                $event->properties[$property->target->name] = $property;
            }
        }
    }
}
