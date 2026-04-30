<?php

declare(strict_types=1);

namespace AutoMapper\Tests\AutoMapperTest\ArraySourceCastDictionary;

use AutoMapper\Tests\AutoMapperBuilder;

class Dto
{
    /** @var array<string, int> */
    public array $scores;

    /** @var array<string, float> */
    public array $rates;
}

return (static function () {
    $autoMapper = AutoMapperBuilder::buildAutoMapper();

    yield $autoMapper->map([
        'scores' => ['alice' => '100', 'bob' => '85'],
        'rates' => ['usd' => '1.0', 'eur' => '0.92'],
    ], Dto::class);
})();
