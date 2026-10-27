<?php

declare(strict_types=1);

namespace AutoMapper\Tests\Doctrine\Hydrator\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class Truck extends Vehicle
{
    #[ORM\Column]
    public int $payload = 0;

    // association only known by the subclass
    #[ORM\ManyToOne(targetEntity: Author::class)]
    public ?Author $driver = null;
}
