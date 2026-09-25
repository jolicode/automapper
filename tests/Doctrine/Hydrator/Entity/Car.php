<?php

declare(strict_types=1);

namespace AutoMapper\Tests\Doctrine\Hydrator\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class Car extends Vehicle
{
    #[ORM\Column]
    public int $doors = 4;
}
