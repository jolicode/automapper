<?php

declare(strict_types=1);

namespace AutoMapper\Tests\Fixtures\MapTo;

use AutoMapper\Attribute\MapTo;

class FooMapTo
{
    public function __construct(
        #[MapTo(Bar::class, name: 'bar')]
        #[MapTo(Bar::class, name: 'baz')]
        public string $foo
    ) {
    }
}
