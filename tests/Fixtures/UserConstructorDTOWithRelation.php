<?php

declare(strict_types=1);

namespace AutoMapper\Tests\Fixtures;

class UserConstructorDTOWithRelation
{
    public IntDTO $int;
    public string $name;
    public int $age;

    public function __construct(IntDTO $int, string $name, int $age = 30)
    {
        $this->int = $int;
        $this->name = $name;
        $this->age = $age;
    }
}
