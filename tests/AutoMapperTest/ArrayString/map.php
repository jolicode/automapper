<?php

declare(strict_types=1);

namespace AutoMapper\Tests\AutoMapperTest\ArrayString;

use AutoMapper\Tests\AutoMapperBuilder;

class Bar
{
    public array $optionValueIds = [];
}

class Foo
{
    private array $optionValueIds = [];

    public function setOptionValueIds(array $optionValueIds): self
    {
        $this->optionValueIds = $optionValueIds;

        return $this;
    }

    public function getOptionValueIds(): array
    {
        return $this->optionValueIds;
    }
}

return (function () {
    $autoMapper = AutoMapperBuilder::buildAutoMapper();
    $bar = new Bar();
    $bar->optionValueIds = ['foo', 'bar', 'baz'];

    yield $autoMapper->map($bar, Foo::class);
})();
