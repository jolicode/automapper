<?php

declare(strict_types=1);

namespace AutoMapper\Tests\Doctrine\Hydrator\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class AccountSettings
{
    #[ORM\Id]
    #[ORM\Column]
    #[ORM\GeneratedValue]
    public ?int $id = null;

    public function __construct(
        #[ORM\OneToOne(targetEntity: Account::class, inversedBy: 'settings')]
        public Account $account,
        #[ORM\Column]
        public string $theme,
    ) {
    }
}
