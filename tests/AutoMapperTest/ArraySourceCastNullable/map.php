<?php

declare(strict_types=1);

namespace AutoMapper\Tests\AutoMapperTest\ArraySourceCastNullable;

use AutoMapper\Tests\AutoMapperBuilder;

class Dto
{
    /** @var array<int|null> */
    public array $values;
}

return (static function () {
    $autoMapper = AutoMapperBuilder::buildAutoMapper();

    yield $autoMapper->map([
        'values' => ['1', null, '3'],
    ], Dto::class);
})();
