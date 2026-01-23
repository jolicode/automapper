<?php

declare(strict_types=1);

namespace AutoMapper\Tests\AutoMapperTest\Nested;

use AutoMapper\Attribute\MapTo;
use AutoMapper\Tests\AutoMapperBuilder;

class UserDto
{
    public function __construct(
        #[MapTo(User::class, property: 'address.zipcode')]
        public string $userAddressZipcode,
        #[MapTo(User::class, property: 'address.city')]
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

$dto = new UserDto(
    userAddressZipcode: '12345',
    userAddressCity: 'Test City',
    name: 'John Doe'
);

return AutoMapperBuilder::buildAutoMapper()->map($dto, User::class);
