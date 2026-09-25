<?php

declare(strict_types=1);

namespace AutoMapper\Tests\Doctrine\Hydrator\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class Rating
{
    public function __construct(
        #[ORM\Id]
        #[ORM\ManyToOne(targetEntity: Author::class)]
        public Author $author,
        #[ORM\Id]
        #[ORM\ManyToOne(targetEntity: Book::class)]
        public Book $book,
        #[ORM\Column]
        public int $score,
    ) {
    }
}
