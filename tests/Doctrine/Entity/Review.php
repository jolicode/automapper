<?php

declare(strict_types=1);

namespace AutoMapper\Tests\Doctrine\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class Review
{
    #[ORM\Column(type: 'integer')]
    public int $rating = 0;

    #[ORM\ManyToOne(targetEntity: Book::class, inversedBy: 'reviews')]
    #[ORM\Id]
    public Book $book;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'reviews')]
    #[ORM\Id]
    public User $user;
}
