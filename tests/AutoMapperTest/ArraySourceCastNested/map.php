<?php

declare(strict_types=1);

namespace AutoMapper\Tests\AutoMapperTest\ArraySourceCastNested;

use AutoMapper\Tests\AutoMapperBuilder;

class Dto
{
    /** @var array<array<int>> */
    public array $matrix;
}

return (static function () {
    $autoMapper = AutoMapperBuilder::buildAutoMapper();

    yield $autoMapper->map([
        'matrix' => [['1', '2'], ['3', '4']],
    ], Dto::class);
})();
