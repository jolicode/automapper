<?php

declare(strict_types=1);

namespace AutoMapper\Tests\Fixtures\ObjectProvider;

use AutoMapper\Attribute\MapProvider;

#[MapProvider(provider: FooProvider::class)]
readonly class Foo
{
    public function __construct(
        private string $bar,
    ) {
    }

    public function getBar(): string
    {
        return $this->bar;
    }
}
