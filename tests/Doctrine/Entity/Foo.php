<?php

declare(strict_types=1);

namespace AutoMapper\Tests\Doctrine\Entity;

use AutoMapper\Attribute\MapProvider;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[MapProvider(provider: false)]
class Foo
{
    #[ORM\Id]
    #[ORM\Column(type: 'integer')]
    #[ORM\GeneratedValue]
    public ?int $id = null;

    #[ORM\Column(type: 'string')]
    public string $foo = '';
}
