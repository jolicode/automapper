<?php

declare(strict_types=1);

namespace AutoMapper\Tests\Doctrine\Hydrator\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class Edition
{
    /** @var Collection<int, Book> */
    #[ORM\OneToMany(targetEntity: Book::class, mappedBy: 'edition')]
    #[ORM\OrderBy(['id' => 'ASC'])]
    public Collection $books;

    public function __construct(
        #[ORM\Id]
        #[ORM\Column(length: 20)]
        public string $isbn,
        #[ORM\Id]
        #[ORM\Column]
        public int $year,
        #[ORM\Column]
        public string $label,
    ) {
        $this->books = new ArrayCollection();
    }
}
