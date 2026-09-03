<?php

declare(strict_types=1);

namespace AutoMapper\Tests\Bundle\Resources\App\Api\Processor;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use AutoMapper\Tests\Bundle\Resources\App\Api\Entity\Review;

final readonly class ReviewProcessor implements ProcessorInterface
{
    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): ?Review
    {
        return $data instanceof Review ? $data : null;
    }
}
