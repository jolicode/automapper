<?php

declare(strict_types=1);

namespace Automapper\Bench\Doctrine\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'book')]
class Book
{
    #[ORM\Id]
    #[ORM\Column(type: 'integer')]
    #[ORM\GeneratedValue]
    public ?int $id = null;

    #[ORM\Column]
    public string $title = '';

    #[ORM\Column]
    public string $isbn = '';

    #[ORM\Column(type: 'integer')]
    public int $pages = 0;

    #[ORM\Column(type: 'float')]
    public float $price = 0.0;

    #[ORM\Column(type: 'datetime_immutable')]
    public \DateTimeImmutable $publishedAt;

    #[ORM\ManyToOne(targetEntity: Author::class, inversedBy: 'books')]
    public ?Author $author = null;

    /** @var Collection<int, Review> */
    #[ORM\OneToMany(targetEntity: Review::class, mappedBy: 'book')]
    public Collection $reviews;

    public function __construct()
    {
        $this->reviews = new ArrayCollection();
        $this->publishedAt = new \DateTimeImmutable();
    }
}
