<?php

declare(strict_types=1);

namespace AutoMapper\Tests\Doctrine\Hydrator\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class Tag
{
    #[ORM\Id]
    #[ORM\Column]
    #[ORM\GeneratedValue]
    public ?int $id = null;

    // hooks are bypassed by Doctrine, which writes the raw value
    #[ORM\Column]
    public string $name {
        set(string $value) {
            $this->name = strtoupper($value);
        }
    }

    /** @var Collection<int, Book> */
    #[ORM\ManyToMany(targetEntity: Book::class, mappedBy: 'tags')]
    #[ORM\OrderBy(['id' => 'ASC'])]
    public Collection $books;

    public function __construct(string $name)
    {
        $this->name = $name;
        $this->books = new ArrayCollection();
    }
}
