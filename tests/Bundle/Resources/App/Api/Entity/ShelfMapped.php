<?php

declare(strict_types=1);

namespace AutoMapper\Tests\Bundle\Resources\App\Api\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use AutoMapper\Attribute\Mapper;
use AutoMapper\Tests\Bundle\Resources\App\Api\Provider\ShelfProvider;
use Doctrine\Common\Collections\Collection;
use Symfony\Component\Serializer\Attribute\Groups;

#[ApiResource(
    operations: [
        new Get(
            uriTemplate: '/shelf-mapped',
            normalizationContext: ['groups' => ['shelf']],
            provider: ShelfProvider::class,
        ),
        new Get(
            uriTemplate: '/shelf-mapped-group',
            normalizationContext: ['groups' => ['group']],
            provider: ShelfProvider::class,
        ),
    ],
)]
#[Mapper(source: 'array', target: 'array')]
class ShelfMapped
{
    public function __construct(
        #[Groups(['shelf', 'group'])]
        public int $id,
        #[Groups(['shelf', 'group'])]
        /** @var Book[] $books */
        public Collection $books,
    ) {
    }
}
