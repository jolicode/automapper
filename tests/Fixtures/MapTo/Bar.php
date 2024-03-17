<?php

declare(strict_types=1);

namespace AutoMapper\Tests\Fixtures\MapTo;

use AutoMapper\Attribute\MapFrom;

class Bar
{
    public function __construct(
        public string $bar,
        public string $baz,
        #[MapFrom(source: FooMapTo::class, name: 'foo')]
        public string $from,
    ) {
    }
}
