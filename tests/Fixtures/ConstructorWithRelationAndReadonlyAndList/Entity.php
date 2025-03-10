<?php

declare(strict_types=1);

namespace AutoMapper\Tests\Fixtures\ConstructorWithRelationAndReadonlyAndList;

class Entity
{
    public function __construct(
        public string $id,
        public array $locales,
        public array $pages,
    ) {
    }
}
