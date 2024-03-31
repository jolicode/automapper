<?php

declare(strict_types=1);

namespace AutoMapper\Tests\Fixtures\Normalizer;

use Symfony\Component\Serializer\Attribute\Groups;

interface GroupDummyInterface
{
    #[Groups(['a', 'name_converter'])]
    public function getSymfony();
}
