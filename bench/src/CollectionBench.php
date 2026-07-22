<?php

declare(strict_types=1);

namespace Automapper\Bench;

use Automapper\Bench\Factory\MapperFactory;
use Automapper\Bench\Factory\PayloadFactory;
use Automapper\Bench\Model\Person;
use AutoMapper\MapperContext;
use PhpBench\Attributes\BeforeMethods;
use PhpBench\Attributes\Groups;
use PhpBench\Attributes\Iterations;
use PhpBench\Attributes\ParamProviders;
use PhpBench\Attributes\Revs;
use Symfony\Component\TypeInfo\Type;

/**
 * Deserializing a large JSON array of {@see Person} objects.
 *
 * This suite is about memory as much as speed: read PHPBench's `mem_peak` column
 * alongside the timing. The eager strategies (mapCollection, Serializer, and the
 * buffered stream readers) hold the whole collection in memory at once; the
 * streaming strategy (`benchAutoMapperJsonStreamerStream`) processes one element
 * at a time and should show a dramatically lower peak on large inputs.
 *
 * IMPORTANT — every subject fully *realizes* its objects: the loop reads a nested
 * field (`$person->address->city`) so the work is comparable across libraries.
 * This matters for Symfony JsonStreamer in particular: `read()` on a `Type::list`
 * of objects returns lazy-ghost instances whose hydration is deferred until a
 * property is touched, so a loop that never reads a property would measure it
 * doing far less work than the mappers that build real objects up front.
 * (`benchJsonDecode` is the exception: it is the no-hydration array floor.)
 *
 * Run with:
 *   php vendor/bin/phpbench run src/CollectionBench.php --report=aggregate
 * and add `mem_peak` to the report to compare memory.
 */
#[Revs(1)]
#[Iterations(3)]
#[BeforeMethods('setUp')]
#[Groups(['collection'])]
class CollectionBench
{
    private string $file;

    private Type $listType;

    private Type $iterableType;

    /**
     * @return array<string, array{count: int}>
     */
    public function provideSizes(): array
    {
        return [
            'small (1k)' => ['count' => 1_000],
            'large (20k)' => ['count' => 20_000],
        ];
    }

    /**
     * @param array{count: int} $params
     */
    public function setUp(array $params): void
    {
        $this->file = PayloadFactory::writePersonListJsonFile($params['count']);
        $this->listType = Type::list(Type::object(Person::class));
        // An *iterable* type with an int key is the shape Symfony JSON streamer
        // decodes lazily from a JSON array: read() returns a Generator that yields
        // one hydrated object at a time instead of materializing the whole list.
        $this->iterableType = Type::iterable(Type::object(Person::class), Type::int());

        // Warm generation with a tiny payload so it is out of the measured run.
        foreach (MapperFactory::autoMapperJsonStreamReader()->read(PayloadFactory::stream(PayloadFactory::personListJson(1)), $this->listType) as $ignored) {
        }
        foreach (MapperFactory::autoMapperNoAttributeJsonStreamReader()->read(PayloadFactory::stream(PayloadFactory::personListJson(1)), $this->listType) as $ignored) {
        }
        MapperFactory::autoMapper()->mapCollection([PayloadFactory::personArray(0)], Person::class);

        // Fail fast if any approach in this group produces a different result.
        Verifier::assertCollection();
    }

    /**
     * @return resource
     */
    private function open()
    {
        return fopen($this->file, 'r');
    }

    /**
     * Fully realize every mapped object by reading a nested field, so each
     * strategy is measured doing the same work (Symfony's lazy ghosts included).
     *
     * @param iterable<Person> $people
     */
    private function consume(iterable $people): int
    {
        $sink = 0;
        foreach ($people as $person) {
            $sink += \strlen($person->address->city);
        }

        return $sink;
    }

    #[ParamProviders('provideSizes')]
    #[Groups(['baseline'])]
    public function benchJsonDecode(): void
    {
        $data = json_decode((string) file_get_contents($this->file), true, 512, JSON_THROW_ON_ERROR);
        $sink = \count($data);
    }

    #[ParamProviders('provideSizes')]
    public function benchAutoMapperMapCollection(): void
    {
        $data = json_decode((string) file_get_contents($this->file), true, 512, JSON_THROW_ON_ERROR);
        $sink = $this->consume(MapperFactory::autoMapper()->mapCollection($data, Person::class));
    }

    #[ParamProviders('provideSizes')]
    public function benchAutoMapperNoCheckingMapCollection(): void
    {
        $data = json_decode((string) file_get_contents($this->file), true, 512, JSON_THROW_ON_ERROR);
        $sink = $this->consume(MapperFactory::autoMapperNoChecking()->mapCollection($data, Person::class));
    }

    #[ParamProviders('provideSizes')]
    public function benchSymfonySerializer(): void
    {
        $objects = MapperFactory::serializer()->deserialize(
            (string) file_get_contents($this->file),
            Person::class . '[]',
            'json',
        );
        $sink = $this->consume($objects);
    }

    #[ParamProviders('provideSizes')]
    public function benchSymfonyJsonStreamerIterable(): void
    {
        $sink = $this->consume(MapperFactory::jsonStreamReader()->read($this->open(), $this->iterableType));
    }

    /**
     * Cautionary counter-example: reading the same JSON array as a `Type::list`
     * materializes the whole collection (see the README), so its peak memory
     * explodes on large inputs. Prefer the iterable subject above.
     */
    #[ParamProviders('provideSizes')]
    public function benchSymfonyJsonStreamerList(): void
    {
        $sink = $this->consume(MapperFactory::jsonStreamReader()->read($this->open(), $this->listType));
    }

    #[ParamProviders('provideSizes')]
    public function benchAutoMapperJsonStreamerBuffered(): void
    {
        $sink = $this->consume(MapperFactory::autoMapperJsonStreamReader()->read($this->open(), $this->listType));
    }

    #[ParamProviders('provideSizes')]
    public function benchAutoMapperJsonStreamerBufferedNoAttributeChecking(): void
    {
        $sink = $this->consume(MapperFactory::autoMapperNoAttributeJsonStreamReader()->read($this->open(), $this->listType));
    }

    #[ParamProviders('provideSizes')]
    public function benchAutoMapperJsonStreamerStream(): void
    {
        $collection = MapperFactory::autoMapperJsonStreamReader()->read(
            $this->open(),
            $this->listType,
            [MapperContext::STREAM => true],
        );
        $sink = $this->consume($collection);
    }

    #[ParamProviders('provideSizes')]
    public function benchAutoMapperJsonStreamerStreamNoAttributeChecking(): void
    {
        $collection = MapperFactory::autoMapperNoAttributeJsonStreamReader()->read(
            $this->open(),
            $this->listType,
            [MapperContext::STREAM => true],
        );
        $sink = $this->consume($collection);
    }
}
