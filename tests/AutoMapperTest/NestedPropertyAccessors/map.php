<?php

declare(strict_types=1);

namespace AutoMapper\Tests\AutoMapperTest\NestedPropertyAccessors;

use AutoMapper\Attribute\MapFrom;
use AutoMapper\Attribute\MapTo;
use AutoMapper\Tests\AutoMapperBuilder;

class Address
{
    public string $zipcode = '';
}

class PrivateAddress
{
    private string $zipcode = '';

    public function __construct(string $zipcode)
    {
        $this->zipcode = $zipcode;
    }
}

class User
{
    public Address $address;
}

class NullableUser
{
    public ?Address $address = null;
}

class PrivateUser
{
    public PrivateAddress $address;
}

class UserDto
{
    #[MapFrom(property: 'address.zipcode')]
    public ?string $zipcode = null;
}

class UserConstructorDto
{
    public function __construct(
        #[MapFrom(property: 'address.zipcode')]
        public ?string $zipcode = null,
    ) {
    }
}

class NestedTarget
{
    public ?Address $address = null;
}

class ZipSource
{
    #[MapTo(target: NestedTarget::class, property: 'address.zipcode')]
    public string $zipcode = '75000';
}

return (static function () {
    $autoMapper = AutoMapperBuilder::buildAutoMapper();

    // uninitialized typed parent property: must be skipped, not crash
    yield 'read-uninitialized-parent' => $autoMapper->map(new User(), UserDto::class);

    // null parent property: must be skipped
    yield 'read-null-parent' => $autoMapper->map(new NullableUser(), UserDto::class);

    // defined parent: nested value is mapped
    $user = new User();
    $user->address = new Address();
    $user->address->zipcode = '13005';

    yield 'read-with-parent' => $autoMapper->map($user, UserDto::class);

    // uninitialized parent through the constructor path: must fall back to the default value
    yield 'construct-uninitialized-parent' => $autoMapper->map(new User(), UserConstructorDto::class);

    yield 'construct-with-parent' => $autoMapper->map($user, UserConstructorDto::class);

    // nested write on a null parent: must be skipped, not crash
    yield 'write-null-parent' => $autoMapper->map(new ZipSource(), NestedTarget::class);

    // nested write on an existing parent
    $target = new NestedTarget();
    $target->address = new Address();

    yield 'write-with-parent' => $autoMapper->map(new ZipSource(), $target);

    // private nested leaf property
    $privateMapper = AutoMapperBuilder::buildAutoMapper(mapPrivatePropertiesAndMethod: true);

    $privateUser = new PrivateUser();
    $privateUser->address = new PrivateAddress('69001');

    yield 'read-private-leaf' => $privateMapper->map($privateUser, UserDto::class);
})();
