<?php

declare(strict_types=1);

namespace AutoMapper\Tests\AutoMapperTest\Nested;

use AutoMapper\Attribute\MapFrom;
use AutoMapper\Attribute\MapTo;
use AutoMapper\Tests\AutoMapperBuilder;

class UserDto
{
    public function __construct(
        #[MapTo(property: 'address.zipcode')]
        #[MapFrom(property: 'address.zipcode')]
        public string $userAddressZipcode,
        #[MapTo(property: 'address.city')]
        #[MapFrom(property: 'address.city')]
        public string $userAddressCity,
        public string $name,
    ) {
    }
}

class User
{
    public string $name;
    public Address $address;

    public function __construct()
    {
        $this->address = new Address();
    }
}

class Address
{
    public string $zipcode;
    public string $city;
}

return (function () {
    $autoMapper = AutoMapperBuilder::buildAutoMapper();
    $dto = new UserDto(
        userAddressZipcode: '12345',
        userAddressCity: 'Test City',
        name: 'John Doe'
    );

    $user = $autoMapper->map($dto, User::class);

    yield 'to-nested' => $user;

    yield 'from-nested' => $autoMapper->map($user, UserDto::class);

    $arrayNested = $autoMapper->map($dto, 'array');

    yield 'to-nested-array' => $arrayNested;

    yield 'from-nested-array' => $autoMapper->map($arrayNested, UserDto::class);
})();
