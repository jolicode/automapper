<?php

declare(strict_types=1);

namespace AutoMapper\Tests\AutoMapperTest\DoctrineCollections;

use AutoMapper\Tests\AutoMapperBuilder;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

class Book
{
    public function __construct(
        public string $name,
    ) {
    }
}

class Library
{
    /** @var Collection<Book> */
    public Collection $books;
}

return (function () {
    $autoMapper = AutoMapperBuilder::buildAutoMapper();

    $library = new Library();
    $library->books = new ArrayCollection([
        new Book('The Empyrean Onyx Storm'),
        new Book('Valentina'),
        new Book('Imbalance'),
    ]);
    yield 'to-array' => $autoMapper->map($library, 'array');

    $data = [
        'books' => [
            ['name' => 'The Empyrean Onyx Storm'],
            ['name' => 'Valentina'],
            ['name' => 'Imbalance'],
        ],
    ];
    yield 'from-array' => $autoMapper->map($data, Library::class);
})();
