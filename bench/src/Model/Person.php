<?php

declare(strict_types=1);

namespace Automapper\Bench\Model;

/**
 * Plain public-property DTO used for the JSON (de)serialization scenarios.
 *
 * It carries a mix of scalars, a nested object, a scalar list and an object
 * list so the benchmark exercises nested hydration and collections, not just
 * flat scalar copying.
 */
class Person
{
    public int $id = 0;
    public string $firstName = '';
    public string $lastName = '';
    public string $email = '';
    public int $age = 0;
    public bool $active = false;
    public float $balance = 0.0;
    public Address $address;

    /** @var list<string> */
    public array $tags = [];

    /** @var list<Address> */
    public array $addresses = [];
}
