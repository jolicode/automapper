<?php

declare(strict_types=1);

namespace AutoMapper\Tests\AutoMapperTest\ArrayNested;

use AutoMapper\Tests\AutoMapperBuilder;

class UserApiResource
{
    public array $roles; // ["ROLE_USER"]
}

#[\AllowDynamicProperties]
class UserEntity
{
    public function setRoles(array $roles): void
    {
        $this->roles = $roles; // [["ROLE_USER"]];
    }
}

return (static function () {
    $autoMapper = AutoMapperBuilder::buildAutoMapper();

    $userApiResource = new UserApiResource();
    $userApiResource->roles = ['ROLE_USER'];

    yield 'array' => $autoMapper->map($userApiResource, UserEntity::class);
})();
