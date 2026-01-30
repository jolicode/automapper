<?php

declare(strict_types=1);

namespace AutoMapper\Tests\AutoMapperTest\DiscriminatorAttributeArray;

use AutoMapper\Attribute\Mapper;
use AutoMapper\Metadata\Discriminator;
use AutoMapper\Tests\AutoMapperBuilder;

class Owner
{
    public function __construct(
        public Pet $pet,
    ) {
    }
}

#[Mapper(target: 'array', discriminator: new Discriminator([
    'dog' => Dog::class,
    'cat' => Cat::class,
], propertyName: 'type'))]
class Pet
{
    public function __construct(
        public string $name,
    ) {
    }
}

class Dog extends Pet
{
    public function __construct(
        public string $name,
        public string $breed,
    ) {
        parent::__construct($name);
    }
}

class Cat extends Pet
{
    public function __construct(
        public string $name,
        public bool $isIndoor,
    ) {
        parent::__construct($name);
    }
}

return (function () {
    $autoMapper = AutoMapperBuilder::buildAutoMapper(mapPrivatePropertiesAndMethod: true);

    $pet = new Dog('Rex', 'German Shepherd');
    $owner = new Owner($pet);
    $dto = $autoMapper->map($owner, 'array');

    yield 'to-dto' => $dto;

    yield 'to-obj' => $autoMapper->map($dto, Owner::class);
})();
