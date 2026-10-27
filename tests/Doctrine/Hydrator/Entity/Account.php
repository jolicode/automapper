<?php

declare(strict_types=1);

namespace AutoMapper\Tests\Doctrine\Hydrator\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class Account
{
    #[ORM\Id]
    #[ORM\Column]
    #[ORM\GeneratedValue]
    public ?int $id = null;

    #[ORM\OneToOne(targetEntity: AccountSettings::class, mappedBy: 'account')]
    public ?AccountSettings $settings = null;

    public function __construct(
        #[ORM\Column]
        public string $login,
    ) {
    }
}
