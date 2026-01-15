<?php

declare(strict_types=1);

namespace AutoMapper\Tests\Bundle\Resources\App\Api\Provider;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use AutoMapper\Tests\Bundle\Resources\App\Api\Entity\Book;
use AutoMapper\Tests\Bundle\Resources\App\Api\Entity\Shelf;
use AutoMapper\Tests\Bundle\Resources\App\Api\Entity\ShelfMapped;
use Doctrine\Common\Collections\ArrayCollection;

class ShelfProvider implements ProviderInterface
{
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): Shelf|ShelfMapped
    {
        $book = new Book();
        $book->title = 'The Book';
        $book->id = 1;

        $secondBook = new Book();
        $secondBook->title = 'Another Book';
        $secondBook->id = 2;

        if ($operation->getClass() === ShelfMapped::class) {
            return new ShelfMapped(1, new ArrayCollection([$book, $secondBook]));
        }

        return new Shelf(1, new ArrayCollection([$book, $secondBook]));
    }
}
