<?php

declare(strict_types=1);

namespace AutoMapper\Tests\Doctrine\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class Review
{
    #[ORM\Id, ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(type: 'integer')]
    public int $rating = 0;

    #[ORM\Column(type: 'string')]
    public string $body = '';

    #[ORM\Column(type: 'string')]
    public string $author = '';

    #[ORM\Column(type: 'datetime')]
    public \DateTimeImmutable $publicationDate;

    #[ORM\ManyToOne(targetEntity: Book::class, inversedBy: 'reviews')]
    public Book $book;
}
