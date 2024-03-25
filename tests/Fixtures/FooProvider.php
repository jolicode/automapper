<?php

declare(strict_types=1);

namespace AutoMapper\Tests\Fixtures;

use AutoMapper\Attribute\MapProvider;
use AutoMapper\Tests\Fixtures\Provider\CustomProvider;

#[MapProvider(provider: CustomProvider::class, source: 'array')]
class FooProvider
{
    public string $foo;

    public string $bar;
}
