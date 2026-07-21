<?php

declare(strict_types=1);

namespace Automapper\Bench\ObjectMapping;

class PersonTarget
{
    public int $id = 0;
    public string $firstName = '';
    public string $lastName = '';
    public string $email = '';
    public int $age = 0;
    public bool $active = false;
    public float $balance = 0.0;
    public AddressTarget $address;

    /** @var list<string> */
    public array $tags = [];
}
