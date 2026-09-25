<?php

declare(strict_types=1);

namespace Automapper\Bench\Doctrine\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'review')]
class Review
{
    #[ORM\Id]
    #[ORM\Column(type: 'integer')]
    #[ORM\GeneratedValue]
    public ?int $id = null;

    #[ORM\Column(type: 'integer')]
    public int $rating = 0;

    #[ORM\Column]
    public string $comment = '';

    #[ORM\Column(type: 'datetime_immutable')]
    public \DateTimeImmutable $createdAt;

    #[ORM\ManyToOne(targetEntity: Book::class, inversedBy: 'reviews')]
    public ?Book $book = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }
}
