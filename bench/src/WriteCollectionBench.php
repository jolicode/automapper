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
 * Writing a **list of {@see Person}** to JSON — the write-side counterpart of
 * {@see CollectionBench} (which reads a list of Person). Read the `mem_peak` column.
 *
 * This compares the two JSON stream *writer* implementations, AutoMapper's and
 * Symfony's, on a top-level list of objects. AutoMapper maps each element to its
 * array shape and streams the JSON array (with `STREAM=true` it never buffers the
 * mapped elements); Symfony encodes straight from the objects with a compiled writer.
 */
#[Revs(1)]
#[Iterations(3)]
#[BeforeMethods('setUp')]
#[Groups(['write-collection'])]
class WriteCollectionBench
{
    /** @var list<Person> */
    private array $persons;

    private Type $listType;

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
        $this->persons = PayloadFactory::personList($params['count']);
        $this->listType = Type::iterable(Type::object(Person::class));

        // Warm generation and assert both writers agree (canonically).
        $automapper = MapperFactory::autoMapperJsonStreamWriter();
        $symfony = MapperFactory::jsonStreamWriter();
        $small = PayloadFactory::personList(3);

        Verifier::assertConsistent('write-collection', [
            'automapper' => Verifier::canonicalize(json_decode((string) $automapper->write($small, $this->listType), true)),
            'symfony' => Verifier::canonicalize(json_decode((string) $symfony->write($small, $this->listType), true)),
        ]);
    }

    #[ParamProviders('provideSizes')]
    public function benchAutoMapperJsonStreamerToString(): void
    {
        $sink = \strlen((string) MapperFactory::autoMapperNoAttributeJsonStreamWriter()->write(
            $this->persons,
            $this->listType,
            [MapperContext::STREAM => true],
        ));
    }

    #[ParamProviders('provideSizes')]
    public function benchAutoMapperJsonStreamerStream(): void
    {
        $sink = 0;
        $result = MapperFactory::autoMapperNoAttributeJsonStreamWriter()->write(
            $this->persons,
            $this->listType,
            [MapperContext::STREAM => true],
        );
        foreach ($result as $chunk) {
            $sink += \strlen($chunk);
        }
    }

    #[ParamProviders('provideSizes')]
    public function benchSymfonyJsonStreamerToString(): void
    {
        $sink = \strlen((string) MapperFactory::jsonStreamWriter()->write($this->persons, $this->listType));
    }

    #[ParamProviders('provideSizes')]
    public function benchSymfonyJsonStreamerIterate(): void
    {
        $sink = 0;
        foreach (MapperFactory::jsonStreamWriter()->write($this->persons, $this->listType) as $chunk) {
            $sink += \strlen($chunk);
        }
    }

    #[ParamProviders('provideSizes')]
    public function benchAutoMapperJsonEncode(): void
    {
        $array = MapperFactory::autoMapperNoAttributeChecking()->mapCollection($this->persons, 'array');
        $sink = \strlen((string) json_encode($array));
    }
}
