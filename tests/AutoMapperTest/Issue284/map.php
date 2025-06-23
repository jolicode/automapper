<?php

declare(strict_types=1);

namespace AutoMapper\Tests\AutoMapperTest\Issue284;

use AutoMapper\Tests\AutoMapperBuilder;

abstract readonly class Fruit
{
    public function __construct(
        public int $weight
    ) {
    }
}

final readonly class Banana extends Fruit
{
}

return AutoMapperBuilder::buildAutoMapper()->map(['weight' => 1], Banana::class);
