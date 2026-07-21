<?php

declare(strict_types=1);

namespace AutoMapper\Tests\AutoMapperTest\EnumToDifferentEnum;

use AutoMapper\Tests\AutoMapperBuilder;

enum SourceStatus: string
{
    case Active = 'active';
    case Inactive = 'inactive';
}

enum TargetStatus: string
{
    case Active = 'active';
    case Inactive = 'inactive';
    case Unknown = 'unknown';
}

enum SharedStatus: string
{
    case Active = 'active';
}

class Source
{
    public SourceStatus $status = SourceStatus::Active;
    public SharedStatus $shared = SharedStatus::Active;
}

class Target
{
    public TargetStatus $status;
    public SharedStatus $shared;
}

return AutoMapperBuilder::buildAutoMapper()->map(new Source(), Target::class);
