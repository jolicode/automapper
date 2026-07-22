<?php

declare(strict_types=1);

namespace AutoMapper\EventListener\Symfony;

use AutoMapper\Event\PropertyMetadataEvent;
use Symfony\Component\Serializer\Mapping\Factory\ClassMetadataFactoryInterface;

final readonly class SerializerIgnoreListener
{
    public function __construct(
        private ClassMetadataFactoryInterface $classMetadataFactory,
    ) {
    }

    public function __invoke(PropertyMetadataEvent $event): void
    {
        if ($event->ignored !== null) {
            return;
        }

        if (!$event->mapperMetadata->isSourceArrayLike() && $this->isIgnoreProperty($event->mapperMetadata->source, $event->source->property)) {
            $event->ignored = true;
            $event->ignoreReason = 'Property is ignored by Symfony Serializer Attribute on Source';

            return;
        }

        if (!$event->mapperMetadata->isTargetArrayLike() && $this->isIgnoreProperty($event->mapperMetadata->target, $event->target->property)) {
            $event->ignored = true;
            $event->ignoreReason = 'Property is ignored by Symfony Serializer Attribute on Target';
        }
    }

    private function isIgnoreProperty(string $class, string $property): bool
    {
        $serializerClassMetadata = $this->classMetadataFactory->getMetadataFor($class);

        foreach ($serializerClassMetadata->getAttributesMetadata() as $serializerAttributeMetadata) {
            if ($serializerAttributeMetadata->getName() === $property) {
                return $serializerAttributeMetadata->isIgnored();
            }
        }

        return false;
    }
}
