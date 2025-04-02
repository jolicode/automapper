<?php

declare(strict_types=1);

namespace AutoMapper\Tests\Doctrine\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class Book
{
    #[ORM\Id]
    #[ORM\Column(type: 'integer')]
    #[ORM\GeneratedValue]
    public ?int $id = null;

    #[ORM\Column(type: 'string')]
    public string $title = '';

    #[ORM\Column(type: 'string')]
    public string $description = '';

    #[ORM\Column(type: 'string')]
    public string $author = '';

    #[ORM\OneToMany(targetEntity: Review::class, mappedBy: 'book', cascade: ['persist', 'remove'])]
    /** @var Collection<int, Review> */
    public Collection $reviews;

    public function __construct()
    {
        $this->reviews = new ArrayCollection();
    }
}
