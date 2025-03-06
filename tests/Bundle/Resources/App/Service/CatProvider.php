<?php

declare(strict_types=1);

namespace AutoMapper\Tests\Bundle\Resources\App\Service;

use AutoMapper\Provider\ProviderInterface;
use AutoMapper\Tests\Bundle\Resources\App\Entity\Cat;

class CatProvider implements ProviderInterface
{
    public function provide(string $targetType, mixed $source, array $context): object|array|null
    {
        return new Cat();
    }
}
