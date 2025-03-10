<?php

declare(strict_types=1);

namespace AutoMapper\Tests\Fixtures\ConstructorWithRelationAndReadonlyAndList;

class Target
{
    /**
     * @param list<string> $locales
     */
    public function __construct(
        private readonly Entity $entity,
        public readonly string $id,
        public array $locales,
        public array $pages,
    ) {
    }

    public function getEntity(): Entity
    {
        return $this->entity;
    }
}
