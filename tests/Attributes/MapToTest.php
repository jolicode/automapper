<?php

declare(strict_types=1);

namespace AutoMapper\Tests\Attributes;

use AutoMapper\Tests\AutoMapperBaseTest;
use AutoMapper\Tests\Fixtures\Attributes\UserDTOWithMapTo;
use AutoMapper\Tests\Fixtures\User;

class MapToTest extends AutoMapperBaseTest
{
    public function testMapTo(): void
    {
        $user = $this->autoMapper->map(new UserDTOWithMapTo(), User::class);

        self::assertSame('name', $user->name);
        self::assertSame(10, $user->age);
    }
}
