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
use Symfony\Component\TypeInfo\Type;

/**
 * Serialization of a single {@see Person} object graph into a **JSON string**.
 *
 * Every subject ends with JSON so they are directly comparable. `benchJsonEncodeArray`
 * is the floor: `json_encode` over an already-array payload, no object traversal.
 * For AutoMapper, producing JSON is normalize (object → array) followed by
 * `json_encode`; the array-only step is measured separately in {@see NormalizeBench}.
 */
#[Revs(2000)]
#[Iterations(5)]
#[BeforeMethods('setUp')]
#[Groups(['serialize'])]
class SerializeBench
{
    private Person $person;

    /** @var array<string, mixed> */
    private array $array;

    private Type $type;

    public function setUp(): void
    {
        $this->person = PayloadFactory::person(1);
        $this->array = PayloadFactory::personArray(1);
        $this->type = Type::object(Person::class);

        MapperFactory::autoMapper()->map($this->person, 'array');
        MapperFactory::autoMapperNoAttributeChecking()->map($this->person, 'array');
        MapperFactory::serializer()->serialize($this->person, 'json');
        (string) MapperFactory::jsonStreamWriter()->write($this->person, $this->type);
        (string) MapperFactory::autoMapperJsonStreamWriter()->write($this->person, $this->type);
        (string) MapperFactory::autoMapperNoAttributeJsonStreamWriter()->write($this->person, $this->type);

        // Fail fast if any approach in this group produces a different result.
        Verifier::assertSerialize();
    }

    /**
     * Fair floor: hand-written normalization of the `Person` graph, then encode —
     * the same input the mappers consume, done by hand.
     */
    #[Groups(['baseline'])]
    public function benchManual(): void
    {
        $data = ManualMapper::normalizePerson($this->person);
        $json = json_encode($data, JSON_THROW_ON_ERROR);
    }

    public function benchAutoMapper(): void
    {
        $data = MapperFactory::autoMapper()->map($this->person, 'array');
        $json = json_encode($data, JSON_THROW_ON_ERROR);
    }

    public function benchAutoMapperNoAttributeChecking(): void
    {
        $data = MapperFactory::autoMapperNoAttributeChecking()->map($this->person, 'array');
        $json = json_encode($data, JSON_THROW_ON_ERROR);
    }

    public function benchSymfonySerializer(): void
    {
        MapperFactory::serializer()->serialize($this->person, 'json');
    }

    public function benchSymfonyJsonStreamer(): void
    {
        $json = (string) MapperFactory::jsonStreamWriter()->write($this->person, $this->type);
    }

    public function benchAutoMapperJsonStreamer(): void
    {
        $json = (string) MapperFactory::autoMapperJsonStreamWriter()->write($this->person, $this->type);
    }

    public function benchAutoMapperJsonStreamerNoAttributeChecking(): void
    {
        $json = (string) MapperFactory::autoMapperNoAttributeJsonStreamWriter()->write($this->person, $this->type);
    }
}
