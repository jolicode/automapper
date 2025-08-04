<?php

declare(strict_types=1);

namespace AutoMapper\EventListener\Symfony;

use AutoMapper\Event\PropertyMetadataEvent;
use Symfony\Component\Serializer\NameConverter\AdvancedNameConverterInterface;
use Symfony\Component\Serializer\NameConverter\NameConverterInterface;

final readonly class AdvancedNameConverterListener
{
    public function __construct(
        private AdvancedNameConverterInterface|NameConverterInterface $nameConverter,
    ) {
    }

    public function __invoke(PropertyMetadataEvent $event): void
    {
        if (($event->mapperMetadata->source === 'array' || $event->mapperMetadata->source === \stdClass::class) && $event->source->property === $event->target->property) {
            $event->source->property = $this->nameConverter->normalize($event->target->property, $event->mapperMetadata->target);
        }

        if (($event->mapperMetadata->target === 'array' || $event->mapperMetadata->target === \stdClass::class) && $event->source->property === $event->target->property) {
            $event->target->property = $this->nameConverter->normalize($event->source->property, $event->mapperMetadata->source);
        }
    }
}
