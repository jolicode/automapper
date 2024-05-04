<?php

declare(strict_types=1);

namespace AutoMapper\Tests\Fixtures\Issue111;

use AutoMapper\Attribute\MapTo;

class FooDto
{
    #[MapTo(target: Foo::class, transformer: ColourTransformer::class)]
    public array $colours = [];
}
