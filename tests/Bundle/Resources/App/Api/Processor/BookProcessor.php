<?php

declare(strict_types=1);

namespace AutoMapper\Tests\Bundle\Resources\App\Api\Processor;

use ApiPlatform\Metadata\HttpOperation;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use AutoMapper\Tests\Bundle\Resources\App\Api\Entity\Book;

final readonly class BookProcessor implements ProcessorInterface
{
    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = [])
    {
        if (!$data instanceof Book) {
            return null;
        }

        if ($operation instanceof HttpOperation && $operation->getMethod() === 'POST') {
            $data->id = random_int(1, 1000);
        }

        return $data;
    }
}
