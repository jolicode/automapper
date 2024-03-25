<?php

declare(strict_types=1);

namespace AutoMapper\Tests\Fixtures;

use AutoMapper\Attribute\MapTo;
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
