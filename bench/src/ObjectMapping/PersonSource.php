<?php

declare(strict_types=1);

namespace Automapper\Bench\ObjectMapping;

use Symfony\Component\ObjectMapper\Attribute\Map;

/**
 * Source object for the object-to-object mapping scenario.
 *
 * The `#[Map]` attributes are read by Symfony ObjectMapper. AutoMapper ignores
 * them and maps by matching property names, which produces the same result here
 * since source and target share the same property names.
 *
 * The workload is scalars + one nested object + a scalar list: exactly the shape
 * that both mappers deep-map identically (Symfony ObjectMapper does not element-wise
 * map object *collections*, so no object list is used here to keep the comparison fair).
 */
#[Map(target: PersonTarget::class)]
class PersonSource
{
    public int $id = 0;
    public string $firstName = '';
    public string $lastName = '';
    public string $email = '';
    public int $age = 0;
    public bool $active = false;
    public float $balance = 0.0;
    public AddressSource $address;

    /** @var list<string> */
    public array $tags = [];
}
