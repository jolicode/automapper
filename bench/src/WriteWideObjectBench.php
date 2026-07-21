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
 * Writing a **single** {@see Person} that carries a large nested `addresses`
 * collection to JSON — the nested-collection counterpart of {@see WriteCollectionBench}
 * (which writes a top-level list). Both exercise the AutoMapper writer's streaming.
 *
 * Consumption modes (all producing identical JSON):
 *  - `__toString()` — buffered: one native `json_encode`, whole string in memory.
 *  - `getIterator()` with `STREAM=true` — no buffering: each element is mapped,
 *    yielded as a JSON chunk, then dropped, so peak stays flat. Single-pass.
 *
 * Note: the source object already holds its `addresses` as a materialized PHP
 * array, a fixed memory floor here; the streaming win is on the *additional* memory
 * the writer allocates (mapped arrays + JSON string).
 */
#[Revs(1)]
#[Iterations(3)]
#[BeforeMethods('setUp')]
#[Groups(['write-wide-object'])]
class WriteWideObjectBench
{
    private Person $person;

    private Type $type;

    /**
     * @return array<string, array{count: int}>
     */
    public function provideSizes(): array
    {
        return [
            'small (5k)' => ['count' => 5_000],
            'large (50k)' => ['count' => 50_000],
        ];
    }

    /**
     * @param array{count: int} $params
     */
    public function setUp(array $params): void
    {
        $this->person = PayloadFactory::widePerson($params['count']);
        $this->type = Type::object(Person::class);

        // Warm generation and assert every writer agrees on the output (compared
        // canonically: Symfony keeps declaration order, AutoMapper sorts keys).
        $writer = MapperFactory::autoMapperJsonStreamWriter();
        $noAttr = MapperFactory::autoMapperNoAttributeJsonStreamWriter();
        $symfony = MapperFactory::jsonStreamWriter();
        $small = PayloadFactory::widePerson(3);

        $stream = function ($w, array $options = []) use ($small): string {
            $json = '';
            foreach ($w->write($small, $this->type, $options) as $chunk) {
                $json .= $chunk;
            }

            return $json;
        };

        Verifier::assertConsistent('write-wide-object', [
            'automapper_buffered' => Verifier::canonicalize(json_decode((string) $writer->write($small, $this->type), true)),
            'automapper_stream' => Verifier::canonicalize(json_decode($stream($writer, [MapperContext::STREAM => true]), true)),
            'automapper_buffered_no_attr' => Verifier::canonicalize(json_decode((string) $noAttr->write($small, $this->type), true)),
            'automapper_stream_no_attr' => Verifier::canonicalize(json_decode($stream($noAttr, [MapperContext::STREAM => true]), true)),
            'symfony_buffered' => Verifier::canonicalize(json_decode((string) $symfony->write($small, $this->type), true)),
            'symfony_stream' => Verifier::canonicalize(json_decode($stream($symfony), true)),
        ]);
    }

    #[ParamProviders('provideSizes')]
    public function benchBufferedToString(): void
    {
        $sink = \strlen((string) MapperFactory::autoMapperJsonStreamWriter()->write($this->person, $this->type));
    }

    #[ParamProviders('provideSizes')]
    public function benchBufferedToStringNoAttributeChecking(): void
    {
        $sink = \strlen((string) MapperFactory::autoMapperNoAttributeJsonStreamWriter()->write($this->person, $this->type));
    }

    #[ParamProviders('provideSizes')]
    public function benchStreamIterate(): void
    {
        $sink = 0;
        $result = MapperFactory::autoMapperJsonStreamWriter()->write(
            $this->person,
            $this->type,
            [MapperContext::STREAM => true],
        );
        foreach ($result as $chunk) {
            $sink += \strlen($chunk);
        }
    }

    #[ParamProviders('provideSizes')]
    public function benchStreamIterateNoAttributeChecking(): void
    {
        $sink = 0;
        $result = MapperFactory::autoMapperNoAttributeJsonStreamWriter()->write(
            $this->person,
            $this->type,
            [MapperContext::STREAM => true],
        );
        foreach ($result as $chunk) {
            $sink += \strlen($chunk);
        }
    }

    #[ParamProviders('provideSizes')]
    public function benchSymfonyJsonStreamerToString(): void
    {
        $sink = \strlen((string) MapperFactory::jsonStreamWriter()->write($this->person, $this->type));
    }

    #[ParamProviders('provideSizes')]
    public function benchSymfonyJsonStreamerIterate(): void
    {
        $sink = 0;
        foreach (MapperFactory::jsonStreamWriter()->write($this->person, $this->type) as $chunk) {
            $sink += \strlen($chunk);
        }
    }
}
