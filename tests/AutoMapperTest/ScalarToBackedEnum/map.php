<?php

declare(strict_types=1);

namespace AutoMapper\Tests\AutoMapperTest\ScalarToBackedEnum;

use AutoMapper\Tests\AutoMapperBuilder;

enum Status: string
{
    case Active = 'active';
    case Inactive = 'inactive';
}

enum Level: int
{
    case Low = 1;
    case High = 10;
}

class Source
{
    public string $status = 'active';
    public int $level = 10;
}

class Target
{
    public Status $status;
    public Level $level;
}

return AutoMapperBuilder::buildAutoMapper()->map(new Source(), Target::class);
