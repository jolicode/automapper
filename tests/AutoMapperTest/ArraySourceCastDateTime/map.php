<?php

declare(strict_types=1);

namespace AutoMapper\Tests\AutoMapperTest\ArraySourceCastDateTime;

use AutoMapper\Tests\AutoMapperBuilder;

class Dto
{
    /** @var array<\DateTimeImmutable> */
    public array $dates;
}

return (static function () {
    $autoMapper = AutoMapperBuilder::buildAutoMapper();

    yield $autoMapper->map([
        'dates' => ['2024-01-15T10:30:00+00:00', '2024-06-20T14:00:00+00:00'],
    ], Dto::class);
})();
