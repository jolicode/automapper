<?php

declare(strict_types=1);

namespace Automapper\Bench;

use Automapper\Bench\Factory\MapperFactory;
use Automapper\Bench\Factory\PayloadFactory;
use Automapper\Bench\Model\Person;
use PhpBench\Attributes\BeforeMethods;
use PhpBench\Attributes\Groups;
use PhpBench\Attributes\Iterations;
use PhpBench\Attributes\Revs;

/**
 * Denormalization of a plain PHP array (the shape `json_decode` produces) into a
 * hydrated {@see Person} object graph, with **no** `json_decode` step — this
 * isolates the mapping cost from JSON parsing (see {@see DeserializeBench} for the
 * full JSON → object path).
 *
 * This is also where the AutoMapper `Configuration` variants are compared, since
 * the config only affects the mapping step.
 *
 * The JSON streamers read from a JSON stream, not an array, so they do not appear
 * here.
 */
#[Revs(2000)]
#[Iterations(5)]
#[BeforeMethods('setUp')]
#[Groups(['denormalize'])]
class DenormalizeBench
{
    /** @var array<string, mixed> */
    private array $array;

    public function setUp(): void
    {
        $this->array = PayloadFactory::personArray(1);

        // Warm up code generation so it never lands in a measured rev.
        MapperFactory::autoMapper()->map($this->array, Person::class);
        MapperFactory::autoMapperEval()->map($this->array, Person::class);
        MapperFactory::autoMapperNoConstructor()->map($this->array, Person::class);
        MapperFactory::autoMapperNoAttributeChecking()->map($this->array, Person::class);
        MapperFactory::serializer()->denormalize($this->array, Person::class);

        // Fail fast if any approach in this group produces a different result.
        Verifier::assertDenormalize();
    }

    public function benchAutoMapper(): void
    {
        MapperFactory::autoMapper()->map($this->array, Person::class);
    }

    public function benchAutoMapperEval(): void
    {
        MapperFactory::autoMapperEval()->map($this->array, Person::class);
    }

    public function benchAutoMapperNoConstructor(): void
    {
        MapperFactory::autoMapperNoConstructor()->map($this->array, Person::class);
    }

    public function benchAutoMapperNoAttributeChecking(): void
    {
        MapperFactory::autoMapperNoAttributeChecking()->map($this->array, Person::class);
    }

    public function benchSymfonySerializer(): void
    {
        MapperFactory::serializer()->denormalize($this->array, Person::class);
    }
}
