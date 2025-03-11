<?php

declare(strict_types=1);

namespace AutoMapper\Tests\AutoMapperTest\Private;

use AutoMapper\Tests\AutoMapperBuilder;

class PrivateUserDTO
{
    /** @var int */
    private $id;

    /** @var string */
    private $firstName;

    /** @var string */
    private $lastName;

    public function getId(): int
    {
        return $this->id;
    }

    public function getFirstName(): string
    {
        return $this->firstName;
    }

    public function getLastName(): string
    {
        return $this->lastName;
    }
}

class PrivateUser
{
    /** @var int */
    private $id;

    /** @var string */
    private $firstName;

    /** @var string */
    private $lastName;

    public function __construct(int $id, string $firstName, string $lastName)
    {
        $this->id = $id;
        $this->firstName = $firstName;
        $this->lastName = $lastName;
    }
}

$autoMapper = AutoMapperBuilder::buildAutoMapper(mapPrivatePropertiesAndMethod: true);

$user = new PrivateUser(10, 'foo', 'bar');

return $autoMapper->map($user, PrivateUserDTO::class);
