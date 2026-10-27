<?php

declare(strict_types=1);

namespace AutoMapper\Tests\Doctrine\Hydrator;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\PostLoadEventArgs;

/**
 * Clears the entity manager the first time an entity of the given class is loaded, which happens in the middle of
 * a hydration when that entity is loaded by a nested query.
 */
final class ClearOnFirstPostLoad
{
    private bool $cleared = false;

    public function __construct(
        private readonly EntityManagerInterface $em,
        /** @var class-string */
        private readonly string $className,
    ) {
    }

    public function postLoad(PostLoadEventArgs $args): void
    {
        if (!$this->cleared && $args->getObject() instanceof $this->className) {
            $this->cleared = true;
            $this->em->clear();
        }
    }
}
