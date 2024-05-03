<?php

declare(strict_types=1);

namespace AutoMapper\EventListener;

use AutoMapper\Attribute\Mapper;
use AutoMapper\Event\GenerateMapperEvent;

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
        /** @var Mapper[] $mappers */
        $mappers = [];

        if ($event->mapperMetadata->sourceReflectionClass) {
            foreach ($event->mapperMetadata->sourceReflectionClass->getAttributes(Mapper::class) as $attribute) {
                /** @var Mapper $mapper */
                $mapper = $attribute->newInstance();

                if ($mapper->target === null) {
                    $mappers[] = $mapper;
                }

                if (\is_string($mapper->target) && $mapper->target === $event->mapperMetadata->target) {
                    $mappers[] = $mapper;
                }

                if (\is_array($mapper->target) && \in_array($event->mapperMetadata->target, $mapper->target, true)) {
                    $mappers[] = $mapper;
                }
            }
        }

        if ($event->mapperMetadata->targetReflectionClass) {
            foreach ($event->mapperMetadata->targetReflectionClass->getAttributes(Mapper::class) as $attribute) {
                /** @var Mapper $mapper */
                $mapper = $attribute->newInstance();

                if ($mapper->source === null) {
                    $mappers[] = $mapper;
                }

                if (\is_string($mapper->source) && $mapper->source === $event->mapperMetadata->source) {
                    $mappers[] = $mapper;
                }

                if (\is_array($mapper->source) && \in_array($event->mapperMetadata->source, $mapper->source, true)) {
                    $mappers[] = $mapper;
                }
            }
        }

        if (0 === \count($mappers)) {
            return;
        }

        // sort by priority
        usort($mappers, fn (Mapper $a, Mapper $b) => $a->priority <=> $b->priority);

        // get mapper with highest priority
        $mapper = $mappers[0];

        $event->checkAttributes ??= $mapper->checkAttributes;
        $event->constructorStrategy ??= $mapper->constructorStrategy;
        $event->allowReadOnlyTargetToPopulate ??= $mapper->allowReadOnlyTargetToPopulate;
    }
}
