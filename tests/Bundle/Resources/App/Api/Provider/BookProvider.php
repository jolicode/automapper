<?php

declare(strict_types=1);

namespace AutoMapper\Tests\Bundle\Resources\App\Api\Provider;

use ApiPlatform\Metadata\CollectionOperationInterface;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use AutoMapper\Tests\Bundle\Resources\App\Api\Entity\Book;

class BookProvider implements ProviderInterface
{
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): object|array|null
    {
        $book = new Book();
        $book->title = 'The Book';
        $book->id = 1;

        if ($operation instanceof CollectionOperationInterface) {
            return [$book];
        }

        return $book;
    }
}
