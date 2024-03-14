<?php

declare(strict_types=1);

namespace AutoMapper\EventListener\Symfony;

use AutoMapper\Event\PropertyMetadataEvent;
use Symfony\Component\Serializer\NameConverter\AdvancedNameConverterInterface;

final readonly class AdvancedNameConverterListener
{
    public function __construct(private AdvancedNameConverterInterface $nameConverter)
    {
    }

    public function __invoke(PropertyMetadataEvent $event): void
    {
        if ($event->mapperMetadata->source === 'array' || $event->mapperMetadata->source === \stdClass::class) {
            $event->source->name = $this->nameConverter->denormalize($event->target->name, $event->mapperMetadata->target);
        }

        if ($event->mapperMetadata->target === 'array' || $event->mapperMetadata->target === \stdClass::class) {
            $event->target->name = $this->nameConverter->normalize($event->source->name, $event->mapperMetadata->source);
        }
    }
}
