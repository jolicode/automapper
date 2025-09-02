<?php

declare(strict_types=1);

namespace AutoMapper\EventListener;

use AutoMapper\Attribute\MapProvider;
use AutoMapper\Event\GenerateMapperEvent;
use AutoMapper\Exception\BadMapDefinitionException;

/**
 * @internal
 */
final readonly class MapProviderListener
{
    public function __construct()
    {
    }

    public function __invoke(GenerateMapperEvent $event): void
    {
        if (!$event->mapperMetadata->targetReflectionClass) {
            return;
        }

        $attributes = $event->mapperMetadata->targetReflectionClass->getAttributes(MapProvider::class);

        if (0 === \count($attributes)) {
            return;
        }

        $provider = null;
        $defaultMapProvider = null;

        foreach ($attributes as $attribute) {
            /** @var MapProvider $mapProvider */
            $mapProvider = $attribute->newInstance();

            if ($mapProvider->source === null) {
                if ($defaultMapProvider !== null) {
                    throw new BadMapDefinitionException(sprintf('multiple default providers found for class "%s"', $event->mapperMetadata->targetReflectionClass->getName()));
                }

                $defaultMapProvider = $mapProvider->provider;
            } elseif ($mapProvider->source === $event->mapperMetadata->source) {
                if ($provider !== null) {
                    throw new BadMapDefinitionException(sprintf('multiple providers found for class "%s"', $event->mapperMetadata->targetReflectionClass->getName()));
                }

                $provider = $mapProvider->provider;
            }
        }

        $eventProvider = $provider ?? $defaultMapProvider;

        if (null === $eventProvider) {
            return;
        }

        if (false === $eventProvider) {
            $event->provider = null;
        } else {
            $event->provider = $eventProvider;
        }
    }
}
