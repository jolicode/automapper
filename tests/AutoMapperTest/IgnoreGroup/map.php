<?php

declare(strict_types=1);

namespace AutoMapper\Tests\AutoMapperTest\IgnoreGroup;

use AutoMapper\Attribute\Mapper;
use AutoMapper\Attribute\MapTo;
use AutoMapper\Tests\AutoMapperBuilder;

#[Mapper(checkGroups: false)]
class GroupIgnore
{
    #[MapTo(groups: ['group2'])]
    public string $id = 'id';

    #[MapTo(ignore: false)]
    public string $name = 'name';
}

$autoMapper = AutoMapperBuilder::buildAutoMapper();

$group = new GroupIgnore();

return $autoMapper->map($group, 'array', ['groups' => ['group2']]);
