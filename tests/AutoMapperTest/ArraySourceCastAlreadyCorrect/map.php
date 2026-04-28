<?php

declare(strict_types=1);

namespace AutoMapper\Tests\AutoMapperTest\ArraySourceCastAlreadyCorrect;

use AutoMapper\Tests\AutoMapperBuilder;

class Dto
{
    /** @var array<int> */
    public array $ids;

    /** @var array<string> */
    public array $names;
}

return (static function () {
    $autoMapper = AutoMapperBuilder::buildAutoMapper();

    // Values already match target types — should pass through without issues
    yield $autoMapper->map([
        'ids' => [1, 2, 3],
        'names' => ['Alice', 'Bob'],
    ], Dto::class);
})();
