<?php

declare(strict_types=1);

namespace Automapper\Bench\Doctrine\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'author')]
class Author
{
    #[ORM\Id]
    #[ORM\Column(type: 'integer')]
    #[ORM\GeneratedValue]
    public ?int $id = null;

    #[ORM\Column]
    public string $name = '';

    #[ORM\Column]
    public string $email = '';

    #[ORM\Column]
    public bool $active = true;

    #[ORM\Column(type: 'datetime_immutable')]
    public \DateTimeImmutable $createdAt;

    /** @var Collection<int, Book> */
    #[ORM\OneToMany(targetEntity: Book::class, mappedBy: 'author')]
    public Collection $books;

    public function __construct()
    {
        $this->books = new ArrayCollection();
        $this->createdAt = new \DateTimeImmutable();
    }
}
