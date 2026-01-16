<?php

declare(strict_types=1);

namespace AutoMapper\Tests\ObjectMapper\Fixtures\EmbeddedMapping;

class User
{
    public string $name;
    public Address $address;

    public function __construct()
    {
        $this->address = new Address();
    }
}
