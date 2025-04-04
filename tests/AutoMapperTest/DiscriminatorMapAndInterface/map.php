<?php

declare(strict_types=1);

namespace AutoMapper\Tests\AutoMapperTest\DiscriminatorMapAndInterface;

use AutoMapper\Tests\AutoMapperBuilder;
use Symfony\Component\Serializer\Attribute\DiscriminatorMap;

#[DiscriminatorMap(typeProperty: 'type', mapping: [
    'type_a' => TypeA::class,
    'type_b' => TypeB::class,
]
)]
interface MyInterface
{
}

class Something
{
    public function __construct(
        public MyInterface $myInterface,
    ) {
    }
}

class TypeA implements MyInterface
{
    public function __construct(
        public string $name,
    ) {
    }
}

class TypeB implements MyInterface
{
    public function __construct(
        public string $age,
    ) {
    }
}

return (function () {
    $autoMapper = AutoMapperBuilder::buildAutoMapper(mapPrivatePropertiesAndMethod: true);

    $something = [
        'myInterface' => [
            'type' => 'type_a',
            'name' => 'my name',
        ],
    ];
    yield 'to-class' => $autoMapper->map($something, Something::class);

    $typeA = new TypeA('my name');
    $something = new Something($typeA);

    yield 'to-array' => $autoMapper->map($something, 'array');
})();
