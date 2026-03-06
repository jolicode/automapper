<?php

declare(strict_types=1);

namespace AutoMapper\Tests\AutoMapperTest\ArraySourceCastShapeOptional;

use AutoMapper\Tests\AutoMapperBuilder;

class Dto
{
    /** @var array{name: string, nickname?: string, age: int} */
    public array $profile;
}

return (function () {
    $autoMapper = AutoMapperBuilder::buildAutoMapper();

    // With optional key present
    yield $autoMapper->map([
        'profile' => ['name' => 'John', 'nickname' => 'Johnny', 'age' => '30'],
    ], Dto::class);

    // Without optional key
    yield $autoMapper->map([
        'profile' => ['name' => 'Jane', 'age' => '25'],
    ], Dto::class);
})();
