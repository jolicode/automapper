<?php

declare(strict_types=1);

namespace AutoMapper\Tests\AutoMapperTest\GroupOverride;

use AutoMapper\Attribute\MapTo;
use AutoMapper\Tests\AutoMapperBuilder;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Serializer\Annotation\Ignore;

class GroupOverride
{
    #[Groups(['group1'])]
    #[MapTo(groups: ['group2'])]
    public string $id = 'id';

    #[Ignore]
    #[MapTo(ignore: false, groups: ['group2'])]
    public string $name = 'name';
}

$autoMapper = AutoMapperBuilder::buildAutoMapper(mapPrivatePropertiesAndMethod: true);

$group = new GroupOverride();

return $autoMapper->map($group, 'array', ['groups' => ['group2']]);
