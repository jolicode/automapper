<?php

declare(strict_types=1);

namespace AutoMapper\Tests\AutoMapperTest\Issue425;

use AutoMapper\Tests\AutoMapperBuilder;

$data = [1, 2, 3, 4, 5];
$foo = new Foo($data);

class Foo
{
    /** @var bigint[] */
    private array $property = [];

    public function __construct(array $property)
    {
        $this->property = $property;
    }

    public function getProperty(): array
    {
        return $this->property;
    }
}

class Bar
{
    public array $property = [];
}

return AutoMapperBuilder::buildAutoMapper()->map($foo, Bar::class);
