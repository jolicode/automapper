<?php

declare(strict_types=1);

namespace AutoMapper\EventListener\Doctrine;

use AutoMapper\Event\GenerateMapperEvent;
use AutoMapper\Provider\Doctrine\DoctrineProvider;
use Doctrine\ORM\EntityManagerInterface;

final readonly class DoctrineProviderListener
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {
    }

    public function __invoke(GenerateMapperEvent $event): void
    {
        if ($event->mapperMetadata->target === 'array' || !$this->entityManager->getMetadataFactory()->hasMetadataFor($event->mapperMetadata->target)) {
            return;
        }

        $event->provider = DoctrineProvider::class;
    }
}
