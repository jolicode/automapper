<?php

declare(strict_types=1);

namespace Automapper\Bench;

use Automapper\Bench\Factory\MapperFactory;
use Automapper\Bench\Factory\PayloadFactory;
use Automapper\Bench\Model\Person;
use Automapper\Bench\ObjectMapping\PersonSource;
use Automapper\Bench\ObjectMapping\PersonTarget;
use Symfony\Component\TypeInfo\Type;

/**
 * Guards benchmark fairness: every approach within a group must produce the same
 * PHP result for the same input, otherwise the timings are comparing apples to
 * oranges. Results are compared *canonically* — normalized to arrays with keys
 * sorted recursively — so a mapper that emits keys in a different order (e.g.
 * AutoMapper sorts them) still counts as equal as long as the data matches.
 *
 * Each benchmark calls the relevant `assert*()` from its `setUp()`, so running
 * `phpbench run` fails fast on any divergence. `bin/verify.php` runs them all and
 * prints a report.
 */
final class Verifier
{
    /**
     * Every denormalization approach (array → object, no JSON) must yield the
     * same {@see Person} graph.
     *
     * @return array<string, mixed> name => canonical result
     */
    public static function denormalizeResults(): array
    {
        $array = PayloadFactory::personArray(1);

        return [
            'automapper' => self::canonicalize(MapperFactory::autoMapper()->map($array, Person::class)),
            'automapper_eval' => self::canonicalize(MapperFactory::autoMapperEval()->map($array, Person::class)),
            'automapper_no_constructor' => self::canonicalize(MapperFactory::autoMapperNoConstructor()->map($array, Person::class)),
            'automapper_no_attribute' => self::canonicalize(MapperFactory::autoMapperNoAttributeChecking()->map($array, Person::class)),
            'symfony_serializer' => self::canonicalize(MapperFactory::serializer()->denormalize($array, Person::class)),
        ];
    }

    /**
     * Every normalization approach (object → array, no JSON) must yield the same
     * array.
     *
     * @return array<string, mixed>
     */
    public static function normalizeResults(): array
    {
        $person = PayloadFactory::person(1);

        return [
            'automapper' => self::canonicalize(MapperFactory::autoMapper()->map($person, 'array')),
            'automapper_no_attribute' => self::canonicalize(MapperFactory::autoMapperNoAttributeChecking()->map($person, 'array')),
            'symfony_serializer' => self::canonicalize(MapperFactory::serializer()->normalize($person)),
        ];
    }

    /**
     * Every deserialization approach (JSON → object) must yield the same
     * {@see Person} graph.
     *
     * @return array<string, mixed>
     */
    public static function deserializeResults(): array
    {
        $json = PayloadFactory::personJson(1);
        $type = Type::object(Person::class);

        return [
            'json_decode' => self::canonicalize(json_decode($json, true)), // the floor: same data, as an array
            'manual' => self::canonicalize(ManualMapper::hydratePerson(json_decode($json, true))),
            'automapper' => self::canonicalize(MapperFactory::autoMapper()->map(json_decode($json, true), Person::class)),
            'automapper_no_attribute' => self::canonicalize(MapperFactory::autoMapperNoAttributeChecking()->map(json_decode($json, true), Person::class)),
            'symfony_serializer' => self::canonicalize(MapperFactory::serializer()->deserialize($json, Person::class, 'json')),
            'symfony_json_streamer' => self::canonicalize(MapperFactory::jsonStreamReader()->read(PayloadFactory::stream($json), $type)),
            'automapper_json_streamer' => self::canonicalize(MapperFactory::autoMapperJsonStreamReader()->read(PayloadFactory::stream($json), $type)),
            'automapper_json_streamer_no_attribute' => self::canonicalize(MapperFactory::autoMapperNoAttributeJsonStreamReader()->read(PayloadFactory::stream($json), $type)),
        ];
    }

    /**
     * Every serialization approach (object → JSON) must yield the same JSON
     * payload (key order aside).
     *
     * @return array<string, mixed>
     */
    public static function serializeResults(): array
    {
        $person = PayloadFactory::person(1);
        $type = Type::object(Person::class);

        return [
            'json_encode' => self::canonicalize(PayloadFactory::personArray(1)),
            'manual' => self::canonicalize(ManualMapper::normalizePerson($person)),
            'automapper' => self::canonicalize(json_decode(json_encode(MapperFactory::autoMapper()->map($person, 'array')), true)),
            'automapper_no_attribute' => self::canonicalize(json_decode(json_encode(MapperFactory::autoMapperNoAttributeChecking()->map($person, 'array')), true)),
            'symfony_serializer' => self::canonicalize(json_decode(MapperFactory::serializer()->serialize($person, 'json'), true)),
            'symfony_json_streamer' => self::canonicalize(json_decode((string) MapperFactory::jsonStreamWriter()->write($person, $type), true)),
            'automapper_json_streamer' => self::canonicalize(json_decode((string) MapperFactory::autoMapperJsonStreamWriter()->write($person, $type), true)),
            'automapper_json_streamer_no_attribute' => self::canonicalize(json_decode((string) MapperFactory::autoMapperNoAttributeJsonStreamWriter()->write($person, $type), true)),
        ];
    }

    /**
     * Every object-to-object approach must yield the same {@see PersonTarget}.
     *
     * @return array<string, mixed>
     */
    public static function objectToObjectResults(): array
    {
        $source = PayloadFactory::personSource(1);

        return [
            'automapper' => self::canonicalize(MapperFactory::autoMapper()->map($source, PersonTarget::class)),
            'automapper_no_attribute' => self::canonicalize(MapperFactory::autoMapperNoAttributeChecking()->map($source, PersonTarget::class)),
            'automapper_object_mapper' => self::canonicalize(MapperFactory::autoMapperObjectMapper()->map($source, PersonTarget::class)),
            'symfony_object_mapper' => self::canonicalize(MapperFactory::symfonyObjectMapper()->map($source)),
        ];
    }

    /**
     * Every collection approach must yield the same list of {@see Person} graphs.
     *
     * @return array<string, mixed>
     */
    public static function collectionResults(int $count = 5): array
    {
        $json = PayloadFactory::personListJson($count);
        $listType = Type::list(Type::object(Person::class));
        $iterableType = Type::iterable(Type::object(Person::class), Type::int());

        return [
            'automapper_map_collection' => self::canonicalize(
                MapperFactory::autoMapper()->mapCollection(json_decode($json, true), Person::class)
            ),
            'symfony_serializer' => self::canonicalize(
                MapperFactory::serializer()->deserialize($json, Person::class . '[]', 'json')
            ),
            'symfony_json_streamer_iterable' => self::canonicalize(
                iterator_to_array(MapperFactory::jsonStreamReader()->read(PayloadFactory::stream($json), $iterableType))
            ),
            'symfony_json_streamer_list' => self::canonicalize(
                MapperFactory::jsonStreamReader()->read(PayloadFactory::stream($json), $listType)
            ),
            'automapper_json_streamer_buffered' => self::canonicalize(
                iterator_to_array(MapperFactory::autoMapperJsonStreamReader()->read(PayloadFactory::stream($json), $listType))
            ),
            'automapper_json_streamer_buffered_no_attribute' => self::canonicalize(
                iterator_to_array(MapperFactory::autoMapperNoAttributeJsonStreamReader()->read(PayloadFactory::stream($json), $listType))
            ),
            'automapper_json_streamer_stream' => self::canonicalize(
                iterator_to_array(MapperFactory::autoMapperJsonStreamReader()->read(
                    PayloadFactory::stream($json),
                    $listType,
                    [\AutoMapper\MapperContext::STREAM => true],
                ))
            ),
            'automapper_json_streamer_stream_no_attribute' => self::canonicalize(
                iterator_to_array(MapperFactory::autoMapperNoAttributeJsonStreamReader()->read(
                    PayloadFactory::stream($json),
                    $listType,
                    [\AutoMapper\MapperContext::STREAM => true],
                ))
            ),
        ];
    }

    public static function assertDenormalize(): void
    {
        self::assertConsistent('denormalize', self::denormalizeResults());
    }

    public static function assertNormalize(): void
    {
        self::assertConsistent('normalize', self::normalizeResults());
    }

    public static function assertDeserialize(): void
    {
        self::assertConsistent('deserialize', self::deserializeResults());
    }

    public static function assertSerialize(): void
    {
        self::assertConsistent('serialize', self::serializeResults());
    }

    public static function assertObjectToObject(): void
    {
        self::assertConsistent('object-to-object', self::objectToObjectResults());
    }

    public static function assertCollection(): void
    {
        self::assertConsistent('collection', self::collectionResults());
    }

    /**
     * Assert every entry equals the first one, throwing a readable diff otherwise.
     *
     * @param array<string, mixed> $results
     */
    public static function assertConsistent(string $group, array $results): void
    {
        $referenceName = null;
        $reference = null;

        foreach ($results as $name => $value) {
            if (null === $referenceName) {
                $referenceName = $name;
                $reference = $value;

                continue;
            }

            if ($value !== $reference) {
                throw new \RuntimeException(\sprintf(
                    "Benchmark group \"%s\" is inconsistent: \"%s\" does not match \"%s\".\n  %s = %s\n  %s = %s",
                    $group,
                    $name,
                    $referenceName,
                    $referenceName,
                    json_encode($reference, JSON_THROW_ON_ERROR),
                    $name,
                    json_encode($value, JSON_THROW_ON_ERROR),
                ));
            }
        }
    }

    /**
     * Normalize an object graph / array to a plain array with keys sorted
     * recursively, so two results are compared by data, not by key order.
     */
    public static function canonicalize(mixed $value): mixed
    {
        $normalized = json_decode(json_encode($value, JSON_THROW_ON_ERROR), true);

        return self::ksortRecursive($normalized);
    }

    private static function ksortRecursive(mixed $value): mixed
    {
        if (!\is_array($value)) {
            return $value;
        }

        // Only sort keys for associative arrays; keep list order intact.
        if (!array_is_list($value)) {
            ksort($value);
        }

        foreach ($value as $key => $item) {
            $value[$key] = self::ksortRecursive($item);
        }

        return $value;
    }
}
