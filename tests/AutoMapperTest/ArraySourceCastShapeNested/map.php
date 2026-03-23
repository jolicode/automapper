<?php

declare(strict_types=1);

namespace AutoMapper\Tests\AutoMapperTest\ArraySourceCastShapeNested;

use AutoMapper\Tests\AutoMapperBuilder;

class Dto
{
    /** @var array{user: array{name: string, age: int}, tags: array<string>, count: int} */
    public array $data;
}

return (function () {
    $autoMapper = AutoMapperBuilder::buildAutoMapper();

    yield $autoMapper->map([
        'data' => [
            'user' => ['name' => 'Alice', 'age' => '28'],
            'tags' => ['php', 'symfony'],
            'count' => '5',
        ],
    ], Dto::class);
})();
