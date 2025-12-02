<?php

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
