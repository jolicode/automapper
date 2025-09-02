<?php

declare(strict_types=1);

namespace AutoMapper\EventListener\Doctrine;

use AutoMapper\Event\PropertyMetadataEvent;
use Doctrine\Persistence\ObjectManager;

final readonly class DoctrineIdentifierListener
{
    public function __construct(
        private ObjectManager $objectManager
    ) {
    }

    public function __invoke(PropertyMetadataEvent $event): void
    {
        if ($event->mapperMetadata->target === 'array' || !$this->objectManager->getMetadataFactory()->hasMetadataFor($event->mapperMetadata->target)) {
            return;
        }

        $metadata = $this->objectManager->getClassMetadata($event->mapperMetadata->target);

        if ($metadata->isIdentifier($event->target->property)) {
            $event->identifier = true;
        }
    }
}
