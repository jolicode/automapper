<?php

declare(strict_types=1);

namespace AutoMapper\Tests\AutoMapperTest\DiscriminatorFromAndTo;

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

#[Mapper(target: PetDto::class, discriminator: new Discriminator([
    DogDto::class => Dog::class,
    CatDto::class => Cat::class,
]))]
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

class OwnerDto
{
    public function __construct(
        public PetDto $pet,
    ) {
    }
}

class PetDto
{
    public function __construct(
        public string $name,
    ) {
    }
}

class DogDto extends PetDto
{
    public function __construct(
        public string $name,
        public string $breed,
    ) {
        parent::__construct($name);
    }
}

class CatDto extends PetDto
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
    $dto = $autoMapper->map($owner, OwnerDto::class);

    yield 'to-dto' => $dto;

    yield 'to-obj' => $autoMapper->map($dto, Owner::class);
})();
