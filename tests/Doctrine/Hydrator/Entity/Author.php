<?php

declare(strict_types=1);

namespace AutoMapper\Tests\Doctrine\Hydrator\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\HasLifecycleCallbacks]
class Author extends Timestampable
{
    #[ORM\Id]
    #[ORM\Column]
    #[ORM\GeneratedValue]
    public ?int $id = null;

    #[ORM\Column]
    private string $name;

    #[ORM\Column(nullable: true)]
    public ?string $email = null;

    #[ORM\Column]
    public bool $active = true;

    /** @var list<string> */
    #[ORM\Column(type: 'json')]
    public array $tags = [];

    #[ORM\Embedded(class: Address::class)]
    public Address $address;

    /** @var Collection<int, Book> */
    #[ORM\OneToMany(targetEntity: Book::class, mappedBy: 'author')]
    #[ORM\OrderBy(['id' => 'ASC'])]
    public Collection $books;

    /** @var Collection<int, Vehicle> */
    #[ORM\OneToMany(targetEntity: Vehicle::class, mappedBy: 'owner')]
    #[ORM\OrderBy(['id' => 'ASC'])]
    public Collection $vehicles;

    public int $postLoadCount = 0;

    public function __construct(string $name)
    {
        $this->name = $name;
        $this->books = new ArrayCollection();
        $this->vehicles = new ArrayCollection();
        $this->address = new Address();
        $this->setCreatedAt(new \DateTimeImmutable('2024-01-01 10:00:00'));
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    #[ORM\PostLoad]
    public function onPostLoad(): void
    {
        ++$this->postLoadCount;
    }
}
