<?php

declare(strict_types=1);

namespace AutoMapper\Tests\Fixtures\Issue189;

class UserPatchInput
{
    public string $lastName;
    public string $firstName;
    public ?\DateTimeImmutable $birthDate;
}
