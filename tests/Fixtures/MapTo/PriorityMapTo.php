<?php

declare(strict_types=1);

namespace AutoMapper\Tests\Fixtures\MapTo;

use AutoMapper\Attribute\MapTo;

class PriorityMapTo
{
    public function __construct(
        #[MapTo('array', ignore: true, priority: 0)]
        #[MapTo('array', ignore: false, priority: 10)]
        public string $foo,
    ) {
    }
}
