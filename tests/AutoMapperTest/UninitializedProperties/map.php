<?php

declare(strict_types=1);

namespace AutoMapper\Tests\AutoMapperTest\UninitializedProperties;

use AutoMapper\MapperContext;
use AutoMapper\Tests\AutoMapperBuilder;

class UserPatchInput
{
    public string $lastName;
    public string $firstName;
    public ?\DateTimeImmutable $birthDate;
}

class User
{
    public string $lastName;
    public string $firstName;
    public ?\DateTimeImmutable $birthDate;

    public function getLastName(): string
    {
        return $this->lastName;
    }

    public function setLastName(string $lastName): self
    {
        $this->lastName = $lastName;

        return $this;
    }

    public function getFirstName(): string
    {
        return $this->firstName;
    }

    public function setFirstName(string $firstName): self
    {
        $this->firstName = $firstName;

        return $this;
    }

    public function getBirthDate(): ?\DateTimeImmutable
    {
        return $this->birthDate;
    }

    public function setBirthDate(?\DateTimeImmutable $birthDate): self
    {
        $this->birthDate = $birthDate;

        return $this;
    }
}

$payload = new UserPatchInput();
$payload->firstName = 'John';
$payload->lastName = 'Doe';

return AutoMapperBuilder::buildAutoMapper()->map(
    $payload,
    User::class,
    [MapperContext::SKIP_UNINITIALIZED_VALUES => true],
);
