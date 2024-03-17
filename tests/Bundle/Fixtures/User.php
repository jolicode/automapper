<?php

declare(strict_types=1);

namespace AutoMapper\Tests\Bundle\Fixtures;

class User extends BaseUser
{
    /**
     * @var AddressDTO
     */
    public $address;

    /**
     * @var AddressDTO[]
     */
    public $addresses = [];
}
