<?php

declare(strict_types=1);

namespace AutoMapper\Tests\Fixtures\Attributes;

use AutoMapper\Attribute\MapTo;
use AutoMapper\Tests\Fixtures\User;

class UserDTOWithMapTo
{
    public int $id = 1;

    #[MapTo('name', User::class)]
    public string $fooName = 'name';

    #[MapTo('age')]
    private int $fooAge = 10;

    public function getFooAge(): int
    {
        return $this->fooAge;
    }
}
