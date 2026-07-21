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
 * Normalization of a single {@see Person} object graph into a plain PHP array,
 * with **no** `json_encode` step — this isolates the mapping cost from JSON
 * encoding (see {@see SerializeBench} for the full object → JSON path).
 *
 * The JSON streamers do not have an array-producing step (they go object → JSON
 * directly), so they do not appear here.
 */
#[Revs(2000)]
#[Iterations(5)]
#[BeforeMethods('setUp')]
#[Groups(['normalize'])]
class NormalizeBench
{
    private Person $person;

    public function setUp(): void
    {
        $this->person = PayloadFactory::person(1);

        MapperFactory::autoMapper()->map($this->person, 'array');
        MapperFactory::autoMapperNoAttributeChecking()->map($this->person, 'array');
        MapperFactory::serializer()->normalize($this->person);

        // Fail fast if any approach in this group produces a different result.
        Verifier::assertNormalize();
    }

    public function benchAutoMapper(): void
    {
        MapperFactory::autoMapper()->map($this->person, 'array');
    }

    public function benchAutoMapperNoAttributeChecking(): void
    {
        MapperFactory::autoMapperNoAttributeChecking()->map($this->person, 'array');
    }

    public function benchSymfonySerializer(): void
    {
        MapperFactory::serializer()->normalize($this->person);
    }
}
