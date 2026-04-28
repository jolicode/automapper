<?php

declare(strict_types=1);

namespace AutoMapper\Tests\AutoMapperTest\ArraySourceCastObjects;

use AutoMapper\Tests\AutoMapperBuilder;

class ItemDto
{
    public int $id;
    public string $name;
}

class ContainerDto
{
    /** @var array<ItemDto> */
    public array $items;
}

return (static function () {
    $autoMapper = AutoMapperBuilder::buildAutoMapper();

    yield $autoMapper->map([
        'items' => [
            ['id' => '1', 'name' => 'First'],
            ['id' => '2', 'name' => 'Second'],
        ],
    ], ContainerDto::class);
})();
