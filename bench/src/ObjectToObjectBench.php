<?php

declare(strict_types=1);

namespace Automapper\Bench;

use Automapper\Bench\Factory\MapperFactory;
use Automapper\Bench\Factory\PayloadFactory;
use Automapper\Bench\ObjectMapping\PersonSource;
use Automapper\Bench\ObjectMapping\PersonTarget;
use PhpBench\Attributes\BeforeMethods;
use PhpBench\Attributes\Groups;
use PhpBench\Attributes\Iterations;
use PhpBench\Attributes\Revs;

/**
 * Object-to-object mapping: {@see PersonSource} to {@see PersonTarget}, both
 * carrying scalars, a nested object and a scalar list.
 *
 * This is the scenario Symfony ObjectMapper targets, so it is the fairest
 * head-to-head between it and AutoMapper. `benchManual` is the hand-written floor.
 */
#[Revs(500)]
#[Iterations(20)]
#[BeforeMethods('setUp')]
#[Groups(['object-to-object'])]
class ObjectToObjectBench
{
    private PersonSource $source;

    public function setUp(): void
    {
        $this->source = PayloadFactory::personSource(1);

        MapperFactory::autoMapper()->map($this->source, PersonTarget::class);
        MapperFactory::autoMapperNoAttributeChecking()->map($this->source, PersonTarget::class);
        MapperFactory::autoMapperObjectMapper()->map($this->source, PersonTarget::class);
        MapperFactory::symfonyObjectMapper()->map($this->source);

        // Fail fast if any approach in this group produces a different result.
        Verifier::assertObjectToObject();
    }

    #[Groups(['baseline'])]
    public function benchManual(): void
    {
        $s = $this->source;
        $target = new PersonTarget();
        $target->id = $s->id;
        $target->firstName = $s->firstName;
        $target->lastName = $s->lastName;
        $target->email = $s->email;
        $target->age = $s->age;
        $target->active = $s->active;
        $target->balance = $s->balance;
        $target->address = new \Automapper\Bench\ObjectMapping\AddressTarget();
        $target->address->street = $s->address->street;
        $target->address->city = $s->address->city;
        $target->address->zipCode = $s->address->zipCode;
        $target->address->country = $s->address->country;
        $target->tags = $s->tags;
    }

    public function benchAutoMapper(): void
    {
        MapperFactory::autoMapper()->map($this->source, PersonTarget::class);
    }

    public function benchAutoMapperNoAttributeChecking(): void
    {
        MapperFactory::autoMapperNoAttributeChecking()->map($this->source, PersonTarget::class);
    }

    public function benchAutoMapperObjectMapper(): void
    {
        MapperFactory::autoMapperObjectMapper()->map($this->source, PersonTarget::class);
    }

    public function benchSymfonyObjectMapper(): void
    {
        MapperFactory::symfonyObjectMapper()->map($this->source);
    }
}
