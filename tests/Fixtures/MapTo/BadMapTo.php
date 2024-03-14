<?php

declare(strict_types=1);

namespace AutoMapper\Tests\Fixtures\MapTo;

use AutoMapper\Attribute\MapTo;

class BadMapTo
{
    public function __construct(
        #[MapTo('array', ignore: true)]
        #[MapTo('array', ignore: false)]
        public string $foo
    ) {
    }
}
