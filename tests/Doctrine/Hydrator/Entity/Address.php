<?php

declare(strict_types=1);

namespace AutoMapper\Tests\Doctrine\Hydrator\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Embeddable]
class Address
{
    public function __construct(
        #[ORM\Column(nullable: true)]
        public ?string $street = null,
        #[ORM\Column(nullable: true)]
        public ?string $city = null,
    ) {
    }
}
