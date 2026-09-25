<?php

declare(strict_types=1);

namespace AutoMapper\Tests\Doctrine\Hydrator\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\InheritanceType('JOINED')]
#[ORM\DiscriminatorColumn(name: 'kind', type: 'string')]
#[ORM\DiscriminatorMap(['car' => Car::class, 'truck' => Truck::class])]
abstract class Vehicle
{
    #[ORM\Id]
    #[ORM\Column]
    #[ORM\GeneratedValue]
    public ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Author::class, inversedBy: 'vehicles')]
    public ?Author $owner = null;

    public function __construct(
        #[ORM\Column]
        public string $name,
    ) {
    }
}
