<?php

declare(strict_types=1);

namespace AutoMapper\Tests\Fixtures\DiscriminatorMapAndInterface;

use Symfony\Component\Serializer\Attribute\DiscriminatorMap;

#[DiscriminatorMap(typeProperty: 'type', mapping: [
    'type_a' => TypeA::class,
    'type_b' => TypeB::class,
]
)]
interface MyInterface
{
}
