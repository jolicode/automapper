<?php

declare(strict_types=1);

namespace AutoMapper\EventListener\Doctrine;

use AutoMapper\Event\PropertyMetadataEvent;
use Doctrine\Persistence\ObjectManager;

final readonly class DoctrineIdentifierListener
{
    public function __construct(
        private ObjectManager $objectManager,
    ) {
    }

    public function __invoke(PropertyMetadataEvent $event): void
    {
        if ($event->mapperMetadata->isTargetArrayLike()) {
            return;
        }

        /** @var class-string $target */
        $target = $event->mapperMetadata->target;

        // isTransient loads the metadata when needed, unlike hasMetadataFor which only checks already loaded ones
        if ($this->objectManager->getMetadataFactory()->isTransient($target)) {
            return;
        }

        $metadata = $this->objectManager->getClassMetadata($target);

        if ($metadata->isIdentifier($event->target->property)) {
            $event->identifier = true;
        }
    }
}
