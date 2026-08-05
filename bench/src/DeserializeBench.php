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
 * Deserialization of a single **JSON string** into a hydrated {@see Person} object
 * graph.
 *
 * Every subject starts from JSON so they are directly comparable. `benchJsonDecode`
 * is the floor: raw `json_decode` with no hydration into typed objects. For
 * AutoMapper, deserializing is `json_decode` followed by denormalize (array →
 * object); the array → object step alone is measured in {@see DenormalizeBench}.
 *
 * IMPORTANT — every object-producing subject fully *realizes* its `Person` graph
 * via {@see realize()}. This matters for Symfony JsonStreamer: `read()` on a
 * `Type::object` returns a lazy-ghost instance whose hydration is deferred until a
 * property is read, so a subject that never touched the result would be measured
 * doing far less work than the mappers that build a real object up front.
 * (`benchJsonDecode` is the exception: it is the no-hydration array floor.)
 */
#[Revs(2000)]
#[Iterations(5)]
#[BeforeMethods('setUp')]
#[Groups(['deserialize'])]
class DeserializeBench
{
    private string $json;

    private Type $type;

    private $stream;

    public function setUp(): void
    {
        $this->json = PayloadFactory::personJson(1);
        $this->type = Type::object(Person::class);
        $this->stream = PayloadFactory::stream($this->json);

        // Warm up code generation / lazy service wiring so it never lands in a measured rev.
        MapperFactory::autoMapper()->map(json_decode($this->json, true), Person::class);
        MapperFactory::autoMapperNoAttributeChecking()->map(json_decode($this->json, true), Person::class);
        MapperFactory::serializer()->deserialize($this->json, Person::class, 'json');
        MapperFactory::jsonStreamReader()->read(PayloadFactory::stream($this->json), $this->type);
        MapperFactory::autoMapperJsonStreamReader()->read(PayloadFactory::stream($this->json), $this->type);
        MapperFactory::autoMapperNoAttributeJsonStreamReader()->read(PayloadFactory::stream($this->json), $this->type);

        // Fail fast if any approach in this group produces a different result.
        Verifier::assertDeserialize();
    }

    /**
     * Read the whole graph so any deferred hydration (Symfony's lazy ghosts) is
     * forced, making every object-producing subject do the same work.
     */
    private function realize(Person $person): int
    {
        $sink = $person->id + \strlen($person->firstName) + \strlen($person->address->city) + \count($person->tags);
        foreach ($person->addresses as $address) {
            $sink += \strlen($address->city);
        }

        return $sink;
    }

    /**
     * Fair floor: decode then hand-written hydration into a `Person` graph — the
     * same output the mappers produce, done by hand.
     */
    #[Groups(['baseline'])]
    public function benchManual(): void
    {
        $data = json_decode($this->json, true, 512, JSON_THROW_ON_ERROR);
        $this->realize(ManualMapper::hydratePerson($data));
    }

    public function benchAutoMapper(): void
    {
        $data = json_decode($this->json, true, 512, JSON_THROW_ON_ERROR);
        $this->realize(MapperFactory::autoMapper()->map($data, Person::class));
    }

    public function benchAutoMapperNoChecking(): void
    {
        $data = json_decode($this->json, true, 512, JSON_THROW_ON_ERROR);
        $this->realize(MapperFactory::autoMapperNoChecking()->map($data, Person::class));
    }

    public function benchSymfonySerializer(): void
    {
        $this->realize(MapperFactory::serializer()->deserialize($this->json, Person::class, 'json'));
    }

    public function benchSymfonyJsonStreamer(): void
    {
        $this->realize(MapperFactory::jsonStreamReader()->read(PayloadFactory::stream($this->json), $this->type));
    }

    public function benchAutoMapperJsonStreamer(): void
    {
        $this->realize(MapperFactory::autoMapperJsonStreamReader()->read(PayloadFactory::stream($this->json), $this->type));
    }

    public function benchAutoMapperJsonStreamerNoAttributeChecking(): void
    {
        $this->realize(MapperFactory::autoMapperNoAttributeJsonStreamReader()->read(PayloadFactory::stream($this->json), $this->type));
    }
}
