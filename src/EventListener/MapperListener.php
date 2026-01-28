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
        /** @var array{0: Mapper, 1: bool}[] $mappers */
        $mappers = [];

        if ($event->mapperMetadata->sourceReflectionClass) {
            foreach ($event->mapperMetadata->sourceReflectionClass->getAttributes(Mapper::class) as $attribute) {
                /** @var Mapper $mapper */
                $mapper = $attribute->newInstance();

                if ($mapper->target === null) {
                    $mappers[] = [$mapper, true];
                }

                if (\is_string($mapper->target) && $mapper->target === $event->mapperMetadata->target) {
                    $mappers[] = [$mapper, true];
                }

                if (\is_array($mapper->target) && \in_array($event->mapperMetadata->target, $mapper->target, true)) {
                    $mappers[] = [$mapper, true];
                }
            }
        }

        if ($event->mapperMetadata->targetReflectionClass) {
            foreach ($event->mapperMetadata->targetReflectionClass->getAttributes(Mapper::class) as $attribute) {
                /** @var Mapper $mapper */
                $mapper = $attribute->newInstance();

                if ($mapper->source === null) {
                    $mappers[] = [$mapper, false];
                }

                if (\is_string($mapper->source) && $mapper->source === $event->mapperMetadata->source) {
                    $mappers[] = [$mapper, false];
                }

                if (\is_array($mapper->source) && \in_array($event->mapperMetadata->source, $mapper->source, true)) {
                    $mappers[] = [$mapper, false];
                }
            }
        }
        if (0 === \count($mappers)) {
            return;
        }

        // sort by priority
        usort($mappers, fn (array $a, array $b) => $a[0]->priority <=> $b[0]->priority);

        // get mapper with highest priority
        [$mapper, $fromSource] = $mappers[0];

        $event->checkAttributes ??= $mapper->checkAttributes;
        $event->constructorStrategy ??= $mapper->constructorStrategy;
        $event->allowReadOnlyTargetToPopulate ??= $mapper->allowReadOnlyTargetToPopulate;
        $event->strictTypes ??= $mapper->strictTypes;
        $event->allowExtraProperties ??= $mapper->allowExtraProperties;
        $event->mapperMetadata->dateTimeFormat = $mapper->dateTimeFormat;

        if ($mapper->discriminator) {
            if ($fromSource) {
                $event->sourceDiscriminator = $mapper->discriminator;
            } else {
                $event->targetDiscriminator = $mapper->discriminator;
            }
        }
    }
}
