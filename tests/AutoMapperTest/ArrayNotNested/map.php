<?php

declare(strict_types=1);

namespace AutoMapper\Tests\AutoMapperTest\ArrayNested;

use AutoMapper\Tests\AutoMapperBuilder;

class UserApiResource
{
    public array $roles; // ["ROLE_USER"]
}

class UserEntity
{
    public function setRoles(array $roles)
    {
        $this->roles = $roles; // [["ROLE_USER"]];
    }
}

return (function () {
    $autoMapper = AutoMapperBuilder::buildAutoMapper();

    $userApiResource = new UserApiResource();
    $userApiResource->roles = ['ROLE_USER'];

    yield 'array' => $autoMapper->map($userApiResource, UserEntity::class);
})();
