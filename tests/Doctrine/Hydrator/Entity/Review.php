<?php

declare(strict_types=1);

namespace AutoMapper\Tests\Doctrine\Hydrator\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class Review
{
    #[ORM\Id]
    #[ORM\Column]
    #[ORM\GeneratedValue]
    public ?int $id = null;

    #[ORM\Column(type: 'text', nullable: true)]
    public ?string $comment = null;

    #[ORM\ManyToOne(targetEntity: Book::class, inversedBy: 'reviews')]
    public Book $book;

    public function __construct(
        #[ORM\Column]
        public int $rating,
    ) {
    }
}
