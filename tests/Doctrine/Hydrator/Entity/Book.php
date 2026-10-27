<?php

declare(strict_types=1);

namespace AutoMapper\Tests\Doctrine\Hydrator\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class Book
{
    #[ORM\Id]
    #[ORM\Column]
    #[ORM\GeneratedValue]
    public ?int $id = null;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2)]
    public string $price = '0.00';

    #[ORM\Column(enumType: BookStatus::class)]
    public BookStatus $status = BookStatus::Draft;

    #[ORM\Column(nullable: true, enumType: BookFormat::class)]
    public ?BookFormat $format = null;

    #[ORM\Column(type: 'date_immutable', nullable: true)]
    public ?\DateTimeImmutable $publishedAt = null;

    #[ORM\ManyToOne(targetEntity: Author::class, inversedBy: 'books')]
    public ?Author $author = null;

    #[ORM\ManyToOne(targetEntity: Publisher::class)]
    public ?Publisher $publisher = null;

    #[ORM\ManyToOne(targetEntity: Edition::class, inversedBy: 'books')]
    #[ORM\JoinColumn(name: 'edition_isbn', referencedColumnName: 'isbn')]
    #[ORM\JoinColumn(name: 'edition_year', referencedColumnName: 'year')]
    public ?Edition $edition = null;

    /** @var Collection<int, Tag> */
    #[ORM\ManyToMany(targetEntity: Tag::class, inversedBy: 'books')]
    #[ORM\OrderBy(['id' => 'ASC'])]
    public Collection $tags;

    /** @var Collection<int, Review> */
    #[ORM\OneToMany(targetEntity: Review::class, mappedBy: 'book')]
    #[ORM\OrderBy(['id' => 'ASC'])]
    public Collection $reviews;

    public function __construct(
        #[ORM\Column]
        public readonly string $title,
    ) {
        $this->tags = new ArrayCollection();
        $this->reviews = new ArrayCollection();
    }
}
