<?php

declare(strict_types=1);

namespace Automapper\Bench;

use Automapper\Bench\Model\Address;
use Automapper\Bench\Model\Person;

/**
 * Hand-written normalize / denormalize for {@see Person}, used as the *fair* floor
 * in the JSON suites: unlike raw `json_encode` / `json_decode`, it produces and
 * consumes the same object graph the mappers do, so the baseline reflects the cost
 * of the object mapping too — just done by hand instead of by a library.
 */
final class ManualMapper
{
    /**
     * @param array<string, mixed> $data
     */
    public static function hydratePerson(array $data): Person
    {
        $person = new Person();
        $person->id = $data['id'];
        $person->firstName = $data['firstName'];
        $person->lastName = $data['lastName'];
        $person->email = $data['email'];
        $person->age = $data['age'];
        $person->active = $data['active'];
        $person->balance = $data['balance'];
        $person->address = self::hydrateAddress($data['address']);
        $person->tags = $data['tags'];

        $addresses = [];
        foreach ($data['addresses'] as $address) {
            $addresses[] = self::hydrateAddress($address);
        }
        $person->addresses = $addresses;

        return $person;
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function hydrateAddress(array $data): Address
    {
        $address = new Address();
        $address->street = $data['street'];
        $address->city = $data['city'];
        $address->zipCode = $data['zipCode'];
        $address->country = $data['country'];

        return $address;
    }

    /**
     * @return array<string, mixed>
     */
    public static function normalizePerson(Person $person): array
    {
        $addresses = [];
        foreach ($person->addresses as $address) {
            $addresses[] = self::normalizeAddress($address);
        }

        return [
            'id' => $person->id,
            'firstName' => $person->firstName,
            'lastName' => $person->lastName,
            'email' => $person->email,
            'age' => $person->age,
            'active' => $person->active,
            'balance' => $person->balance,
            'address' => self::normalizeAddress($person->address),
            'tags' => $person->tags,
            'addresses' => $addresses,
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function normalizeAddress(Address $address): array
    {
        return [
            'street' => $address->street,
            'city' => $address->city,
            'zipCode' => $address->zipCode,
            'country' => $address->country,
        ];
    }
}
