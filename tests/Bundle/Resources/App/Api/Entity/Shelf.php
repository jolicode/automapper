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
            uriTemplate: '/shelf',
            normalizationContext: ['groups' => ['shelf']],
            provider: ShelfProvider::class,
        ),
    ],
)]
#[Mapper(source: 'array', target: 'array')]
class Shelf
{
    public function __construct(
        #[Groups('shelf')]
        public int $id,
        #[Groups('shelf')]
        /** @var Book[] $books */
        public Collection $books,
    )
    {
    }
}
