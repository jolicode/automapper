<?php

declare(strict_types=1);

namespace Automapper\Bench\Factory;

use Automapper\Bench\Model\Address;
use Automapper\Bench\Model\Person;
use Automapper\Bench\ObjectMapping\AddressSource;
use Automapper\Bench\ObjectMapping\PersonSource;

/**
 * Builds the benchmark payloads in every shape the suites need:
 * plain arrays, {@see Person} graphs, {@see PersonSource} graphs and JSON strings.
 *
 * All shapes describe the exact same data so results are directly comparable.
 */
final class PayloadFactory
{
    /**
     * A single person as a plain PHP array (the shape produced by json_decode).
     *
     * @return array<string, mixed>
     */
    public static function personArray(int $seed = 0): array
    {
        return [
            'id' => $seed,
            'firstName' => 'First' . $seed,
            'lastName' => 'Last' . $seed,
            'email' => 'user' . $seed . '@example.com',
            'age' => 20 + ($seed % 50),
            'active' => 0 === $seed % 2,
            'balance' => 1000.5 + $seed,
            'address' => self::addressArray($seed),
            'tags' => ['tag-a', 'tag-b', 'tag-c'],
            'addresses' => [
                self::addressArray($seed),
                self::addressArray($seed + 1),
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function addressArray(int $seed = 0): array
    {
        return [
            'street' => $seed . ' Main Street',
            'city' => 'City' . $seed,
            'zipCode' => str_pad((string) ($seed % 100000), 5, '0', STR_PAD_LEFT),
            'country' => 'Country' . ($seed % 20),
        ];
    }

    /**
     * A list of person arrays.
     *
     * @return list<array<string, mixed>>
     */
    public static function personArrayList(int $count): array
    {
        $list = [];
        for ($i = 0; $i < $count; ++$i) {
            $list[] = self::personArray($i);
        }

        return $list;
    }

    public static function person(int $seed = 0): Person
    {
        $person = new Person();
        $person->id = $seed;
        $person->firstName = 'First' . $seed;
        $person->lastName = 'Last' . $seed;
        $person->email = 'user' . $seed . '@example.com';
        $person->age = 20 + ($seed % 50);
        $person->active = 0 === $seed % 2;
        $person->balance = 1000.5 + $seed;
        $person->address = self::address($seed);
        $person->tags = ['tag-a', 'tag-b', 'tag-c'];
        $person->addresses = [self::address($seed), self::address($seed + 1)];

        return $person;
    }

    public static function address(int $seed = 0): Address
    {
        $address = new Address();
        $address->street = $seed . ' Main Street';
        $address->city = 'City' . $seed;
        $address->zipCode = str_pad((string) ($seed % 100000), 5, '0', STR_PAD_LEFT);
        $address->country = 'Country' . ($seed % 20);

        return $address;
    }

    /**
     * @return list<Person>
     */
    public static function personList(int $count): array
    {
        $list = [];
        for ($i = 0; $i < $count; ++$i) {
            $list[] = self::person($i);
        }

        return $list;
    }

    public static function personSource(int $seed = 0): PersonSource
    {
        $person = new PersonSource();
        $person->id = $seed;
        $person->firstName = 'First' . $seed;
        $person->lastName = 'Last' . $seed;
        $person->email = 'user' . $seed . '@example.com';
        $person->age = 20 + ($seed % 50);
        $person->active = 0 === $seed % 2;
        $person->balance = 1000.5 + $seed;

        $address = new AddressSource();
        $address->street = $seed . ' Main Street';
        $address->city = 'City' . $seed;
        $address->zipCode = str_pad((string) ($seed % 100000), 5, '0', STR_PAD_LEFT);
        $address->country = 'Country' . ($seed % 20);
        $person->address = $address;
        $person->tags = ['tag-a', 'tag-b', 'tag-c'];

        return $person;
    }

    /**
     * A single person carrying a large `addresses` collection — used to exercise the
     * JSON stream *writer*, whose streaming (`STREAM`) mode keeps peak memory flat by
     * yielding the nested collection element by element instead of buffering it.
     */
    public static function widePerson(int $addressCount): Person
    {
        $person = self::person(1);
        $person->addresses = [];
        for ($i = 0; $i < $addressCount; ++$i) {
            $person->addresses[] = self::address($i);
        }

        return $person;
    }

    public static function personJson(int $seed = 0): string
    {
        return json_encode(self::personArray($seed), JSON_THROW_ON_ERROR);
    }

    public static function personListJson(int $count): string
    {
        return json_encode(self::personArrayList($count), JSON_THROW_ON_ERROR);
    }

    /**
     * Write a large JSON list to a temp file and return its path.
     *
     * Used by the memory-focused suites: a stream reader can consume the file
     * without ever holding the whole decoded structure in memory.
     */
    public static function writePersonListJsonFile(int $count): string
    {
        $path = sys_get_temp_dir() . '/automapper-bench-persons-' . $count . '.json';

        if (is_file($path)) {
            return $path;
        }

        $handle = fopen($path, 'w');
        fwrite($handle, '[');
        for ($i = 0; $i < $count; ++$i) {
            if ($i > 0) {
                fwrite($handle, ',');
            }
            fwrite($handle, json_encode(self::personArray($i), JSON_THROW_ON_ERROR));
        }
        fwrite($handle, ']');
        fclose($handle);

        return $path;
    }

    /**
     * @return resource
     */
    public static function stream(string $json)
    {
        $stream = fopen('php://memory', 'r+');
        fwrite($stream, $json);
        rewind($stream);

        return $stream;
    }
}
