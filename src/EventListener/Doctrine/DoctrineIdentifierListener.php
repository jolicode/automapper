<?php

declare(strict_types=1);

namespace AutoMapper\EventListener\Doctrine;

use AutoMapper\Event\PropertyMetadataEvent;
use Doctrine\ORM\EntityManagerInterface;

final readonly class DoctrineIdentifierListener
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {
    }

    public function __invoke(PropertyMetadataEvent $event): void
    {
        if ($event->mapperMetadata->target === 'array' || !$this->entityManager->getMetadataFactory()->hasMetadataFor($event->mapperMetadata->target)) {
            return;
        }

        $metadata = $this->entityManager->getClassMetadata($event->mapperMetadata->target);

        if ($metadata->isIdentifier($event->target->property)) {
            $event->identifier = true;
        }
    }
}
