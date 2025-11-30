<?php

declare(strict_types=1);

namespace AutoMapper\EventListener\Symfony;

use AutoMapper\Event\PropertyMetadataEvent;
use Symfony\Component\Serializer\NameConverter\NameConverterInterface;

final readonly class NameConverterListener
{
    public function __construct(
        private NameConverterInterface $nameConverter,
    ) {
    }

    public function __invoke(PropertyMetadataEvent $event): void
    {
        if (($event->mapperMetadata->source === 'array' || $event->mapperMetadata->source === \stdClass::class) && $event->source->property === $event->target->property) {
            /** @var class-string $target */
            $target = $event->mapperMetadata->target;

            $event->source->property = $this->nameConverter->normalize($event->target->property, $target);
        }

        if (($event->mapperMetadata->target === 'array' || $event->mapperMetadata->target === \stdClass::class) && $event->source->property === $event->target->property) {
            /** @var class-string $source */
            $source = $event->mapperMetadata->source;

            $event->target->property = $this->nameConverter->normalize($event->source->property, $source);
        }
    }
}
