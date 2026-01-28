<?php

declare(strict_types=1);

namespace AutoMapper\EventListener\Doctrine;

use AutoMapper\Event\GenerateMapperEvent;
use AutoMapper\Metadata\Provider;
use AutoMapper\Provider\Doctrine\DoctrineProvider;
use Doctrine\Persistence\ObjectManager;

final readonly class DoctrineProviderListener
{
    public function __construct(
        private ObjectManager $objectManager,
    ) {
    }

    public function __invoke(GenerateMapperEvent $event): void
    {
        if ($event->mapperMetadata->target === 'array' || !$this->objectManager->getMetadataFactory()->hasMetadataFor($event->mapperMetadata->target)) {
            return;
        }

        $event->provider ??= new Provider(Provider::TYPE_SERVICE, DoctrineProvider::class);
    }
}
