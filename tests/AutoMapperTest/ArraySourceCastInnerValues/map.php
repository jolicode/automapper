<?php

declare(strict_types=1);

namespace AutoMapper\Tests\AutoMapperTest\ArraySourceCastInnerValues;

use AutoMapper\Tests\AutoMapperBuilder;

class Dto
{
    /** @var array<int> */
    public array $ids;

    /** @var array<float> */
    public array $prices;

    /** @var array<string> */
    public array $labels;

    /** @var array<bool> */
    public array $flags;
}

return (static function () {
    $autoMapper = AutoMapperBuilder::buildAutoMapper();

    yield $autoMapper->map([
        'ids' => ['1', '2', '3'],
        'prices' => ['9.99', '19.99'],
        'labels' => [1, 2, 3],
        'flags' => [1, 0, '1', ''],
    ], Dto::class);
})();
