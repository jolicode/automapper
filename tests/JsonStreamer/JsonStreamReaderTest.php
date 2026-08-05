<?php

declare(strict_types=1);

namespace AutoMapper\Tests\JsonStreamer;

use AutoMapper\JsonStreamer\JsonStreamReader;
use AutoMapper\MapperContext;
use AutoMapper\Tests\AutoMapperBuilder;
use AutoMapper\Tests\AutoMapperTestCase;
use AutoMapper\Tests\Fixtures;
use Symfony\Component\JsonStreamer\JsonStreamReader as FallbackJsonStreamReader;
use Symfony\Component\TypeInfo\Type;

/**
 * @covers \AutoMapper\JsonStreamer\JsonStreamReader
 */
class JsonStreamReaderTest extends AutoMapperTestCase
{
    /**
     * @return resource
     */
    private function stream(string $json)
    {
        $stream = fopen('php://temp', 'r+');
        fwrite($stream, $json);
        rewind($stream);

        return $stream;
    }

    public function testReadSingleObject(): void
    {
        $autoMapper = AutoMapperBuilder::buildAutoMapper(
            mapPrivatePropertiesAndMethod: true,
            classPrefix: 'JsonStreamReaderPrivate_',
        );

        $reader = new JsonStreamReader($autoMapper, FallbackJsonStreamReader::create());

        $json = '{"id":1,"_id":1,"name":"yolo","age":"13","address":{"city":"Toulon"},"addresses":[{"city":"Toulon"}],"money":20.1}';

        $user = $reader->read($this->stream($json), Type::object(Fixtures\User::class));

        self::assertInstanceOf(Fixtures\User::class, $user);
        self::assertSame(1, $user->getId());
        self::assertSame('yolo', $user->name);
        self::assertInstanceOf(Fixtures\Address::class, $user->address);
        self::assertCount(1, $user->addresses);
        self::assertInstanceOf(Fixtures\Address::class, $user->addresses[0]);
    }

    public function testReadCollectionOfObjectsIsLazy(): void
    {
        $autoMapper = AutoMapperBuilder::buildAutoMapper(
            mapPrivatePropertiesAndMethod: true,
            classPrefix: 'JsonStreamReaderPrivate_',
        );

        $reader = new JsonStreamReader($autoMapper, FallbackJsonStreamReader::create());

        $json = '[{"id":1,"name":"a","age":"10"},{"id":2,"name":"b","age":"20"},{"id":3,"name":"c","age":"30"}]';

        $users = $reader->read($this->stream($json), Type::list(Type::object(Fixtures\User::class)));

        self::assertInstanceOf(\Traversable::class, $users, 'A collection of objects is streamed lazily.');

        $result = [];
        foreach ($users as $key => $user) {
            self::assertInstanceOf(Fixtures\User::class, $user);
            $result[$key] = $user->name;
        }

        self::assertSame([0 => 'a', 1 => 'b', 2 => 'c'], $result);
    }

    public function testCollectionIsBufferedAndReiterable(): void
    {
        $autoMapper = AutoMapperBuilder::buildAutoMapper(
            mapPrivatePropertiesAndMethod: true,
            classPrefix: 'JsonStreamReaderPrivate_',
        );

        $reader = new JsonStreamReader($autoMapper, FallbackJsonStreamReader::create());

        $json = '[{"id":1,"name":"a","age":"10"},{"id":2,"name":"b","age":"20"}]';

        $users = $reader->read($this->stream($json), Type::list(Type::object(Fixtures\User::class)));

        self::assertInstanceOf(\Countable::class, $users);
        self::assertCount(2, $users);

        $firstPass = iterator_to_array($users);
        $secondPass = iterator_to_array($users);

        self::assertSame(['a', 'b'], array_map(static fn (Fixtures\User $u) => $u->name, $firstPass));
        // Buffered: the same mapped instances are replayed, not re-mapped.
        self::assertSame($firstPass[0], $secondPass[0]);
        self::assertSame($firstPass[1], $secondPass[1]);
    }

    public function testStreamOptionDisablesBufferingAndIsSinglePass(): void
    {
        $autoMapper = AutoMapperBuilder::buildAutoMapper(
            mapPrivatePropertiesAndMethod: true,
            classPrefix: 'JsonStreamReaderPrivate_',
        );

        $reader = new JsonStreamReader($autoMapper, FallbackJsonStreamReader::create());

        $json = '[{"id":1,"name":"a","age":"10"},{"id":2,"name":"b","age":"20"}]';

        $users = $reader->read(
            $this->stream($json),
            Type::list(Type::object(Fixtures\User::class)),
            [MapperContext::STREAM => true],
        );

        $names = [];
        foreach ($users as $user) {
            $names[] = $user->name;
        }
        self::assertSame(['a', 'b'], $names);

        // Second iteration must fail: the stream has already been consumed.
        $this->expectException(\LogicException::class);
        iterator_to_array($users);
    }

    public function testReadDictOfObjectsPreservesKeys(): void
    {
        $autoMapper = AutoMapperBuilder::buildAutoMapper(
            mapPrivatePropertiesAndMethod: true,
            classPrefix: 'JsonStreamReaderPrivate_',
        );

        $reader = new JsonStreamReader($autoMapper, FallbackJsonStreamReader::create());

        $json = '{"first":{"id":1,"name":"a","age":"10"},"second":{"id":2,"name":"b","age":"20"}}';

        $users = $reader->read(
            $this->stream($json),
            Type::dict(Type::object(Fixtures\User::class)),
        );

        $result = [];
        foreach ($users as $key => $user) {
            self::assertInstanceOf(Fixtures\User::class, $user);
            $result[$key] = $user->getId();
        }

        self::assertSame(['first' => 1, 'second' => 2], $result);
    }

    public function testRoundTripWithWriter(): void
    {
        $autoMapper = AutoMapperBuilder::buildAutoMapper(
            mapPrivatePropertiesAndMethod: true,
            classPrefix: 'JsonStreamReaderPrivate_',
        );

        $reader = new JsonStreamReader($autoMapper, FallbackJsonStreamReader::create());
        $writer = new \AutoMapper\JsonStreamer\JsonStreamWriter(
            $autoMapper,
            \Symfony\Component\JsonStreamer\JsonStreamWriter::create(),
        );

        $address = new Fixtures\Address();
        $address->setCity('Toulon');
        $user = new Fixtures\User(1, 'yolo', '13');
        $user->address = $address;
        $user->addresses[] = $address;
        $user->money = 20.1;

        $json = (string) $writer->write($user, Type::object(Fixtures\User::class));

        $user2 = $reader->read($this->stream($json), Type::object(Fixtures\User::class));

        self::assertEquals($user, $user2);
    }
}
