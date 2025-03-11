<?php

declare(strict_types=1);

namespace AutoMapper\Tests\AutoMapperTest\ConstructorWithRelation;

use AutoMapper\MapperContext;
use AutoMapper\Tests\AutoMapperBuilder;

readonly class IntDto
{
    public function __construct(
        public int $foo,
    ) {
    }
}

class UserConstructorDTOWithRelation
{
    public IntDto $int;
    public string $name;
    public int $age;

    public function __construct(IntDto $int, string $name, int $age = 30)
    {
        $this->int = $int;
        $this->name = $name;
        $this->age = $age;
    }
}

return (function () {
    $autoMapper = AutoMapperBuilder::buildAutoMapper();

    $user = ['name' => 'foo'];
    try {
        $autoMapper->map($user, UserConstructorDTOWithRelation::class);
        throw new \Exception('Exception not thrown');
    } catch (\throwable $e) {
        yield 'constructor-and-relation-missing' => $e;
    }

    $user = ['name' => 'foo', 'int' => ['foo' => 1]];
    yield 'ok' => $autoMapper->map($user, UserConstructorDTOWithRelation::class);

    $user = ['name' => 'foo'];
    yield 'constructor-arguments' => $autoMapper->map($user, UserConstructorDTOWithRelation::class, [
        MapperContext::CONSTRUCTOR_ARGUMENTS => [
            UserConstructorDTOWithRelation::class => ['int' => new IntDTO(1)],
        ],
    ]);
})();
