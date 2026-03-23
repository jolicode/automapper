<?php

declare(strict_types=1);

namespace AutoMapper\Tests\AutoMapperTest\ArraySourceCastShape;

use AutoMapper\Tests\AutoMapperBuilder;

class Dto
{
    /** @var array{name: string, age: int, score: float, active: bool} */
    public array $profile;
}

return (function () {
    $autoMapper = AutoMapperBuilder::buildAutoMapper();

    yield $autoMapper->map([
        'profile' => ['name' => 'John', 'age' => '30', 'score' => '9.5', 'active' => '1'],
    ], Dto::class);
})();
