<?php

declare(strict_types=1);

namespace AutoMapper\Tests\AutoMapperTest\ArrayString;

use AutoMapper\Tests\AutoMapperBuilder;

class Bar
{
    /** @var string[] */
    public array $optionValueIds = [];
}

class Foo
{
    /** @var string[] */
    public array $optionValueIds = [];
}

return (function () {
    $autoMapper = AutoMapperBuilder::buildAutoMapper();
    $bar = new Bar();
    $bar->optionValueIds = ['foo', 'bar', 'baz'];

    yield $autoMapper->map($bar, Foo::class);
})();
