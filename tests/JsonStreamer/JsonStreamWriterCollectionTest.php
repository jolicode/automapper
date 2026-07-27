<?php

declare(strict_types=1);

namespace AutoMapper\Tests\JsonStreamer;

use AutoMapper\Exception\CircularReferenceException;
use AutoMapper\JsonStreamer\JsonStreamWriter;
use AutoMapper\Tests\AutoMapperBuilder;
use AutoMapper\Tests\AutoMapperTestCase;
use AutoMapper\Tests\Fixtures;
use Symfony\Component\JsonStreamer\JsonStreamWriter as FallbackJsonStreamWriter;
use Symfony\Component\TypeInfo\Type;

/**
 * @covers \AutoMapper\JsonStreamer\JsonStreamWriter
 */
class JsonStreamWriterCollectionTest extends AutoMapperTestCase
{
    private function writer(): JsonStreamWriter
    {
        return new JsonStreamWriter(
            AutoMapperBuilder::buildAutoMapper(
                mapPrivatePropertiesAndMethod: true,
                classPrefix: 'JsonStreamerCollection_',
            ),
            FallbackJsonStreamWriter::create(),
        );
    }

    private function address(string $city): Fixtures\Address
    {
        $address = new Fixtures\Address();
        $address->setCity($city);

        return $address;
    }

    public function testListIsWrittenAsAJsonArray(): void
    {
        $json = (string) $this->writer()->write(
            [$this->address('Paris'), $this->address('Lyon')],
            Type::list(Type::object(Fixtures\Address::class)),
        );

        self::assertSame([['city' => 'Paris'], ['city' => 'Lyon']], json_decode($json, true, flags: \JSON_THROW_ON_ERROR));
    }

    public function testDictIsWrittenAsAJsonObjectKeepingItsKeys(): void
    {
        $json = (string) $this->writer()->write(
            ['first' => $this->address('Paris'), 'second' => $this->address('Lyon')],
            Type::dict(Type::object(Fixtures\Address::class)),
        );

        self::assertSame(
            ['first' => ['city' => 'Paris'], 'second' => ['city' => 'Lyon']],
            json_decode($json, true, flags: \JSON_THROW_ON_ERROR),
        );
    }

    public function testDictKeysAreEscaped(): void
    {
        $json = (string) $this->writer()->write(
            ['a"b' => $this->address('Paris')],
            Type::dict(Type::object(Fixtures\Address::class)),
        );

        self::assertSame(['a"b' => ['city' => 'Paris']], json_decode($json, true, flags: \JSON_THROW_ON_ERROR));
    }

    public function testFalsyScalarElementsAreNotTurnedIntoNull(): void
    {
        // json_encode(0) returns the falsy string "0": it must not be mistaken for a failure
        $json = (string) $this->writer()->write(
            [0, 1, '', false, 'a'],
            Type::list(Type::object(Fixtures\Address::class)),
        );

        self::assertSame([0, 1, '', false, 'a'], json_decode($json, true, flags: \JSON_THROW_ON_ERROR));
    }

    public function testCircularReferenceIsReported(): void
    {
        $autoMapper = AutoMapperBuilder::buildAutoMapper(classPrefix: 'JsonStreamerCircular_');

        $first = new Fixtures\CircularNode('first');
        $second = new Fixtures\CircularNode('second');
        $first->next = $second;
        $second->next = $first;

        $this->expectException(CircularReferenceException::class);

        $stream = $autoMapper->getMapper(Fixtures\CircularNode::class, 'json')->map($first);

        foreach ($stream as $ignored) {
        }
    }

    public function testRepeatedInstanceInSiblingsIsNotACircularReference(): void
    {
        $autoMapper = AutoMapperBuilder::buildAutoMapper(classPrefix: 'JsonStreamerCircular_');

        $shared = new Fixtures\CircularNode('shared');
        $root = new Fixtures\CircularNode('root');
        $root->next = $shared;

        $json = '';
        foreach ($autoMapper->getMapper(Fixtures\CircularNode::class, 'json')->map($root) as $chunk) {
            $json .= $chunk;
        }

        self::assertSame(
            ['name' => 'root', 'next' => ['name' => 'shared', 'next' => null]],
            json_decode($json, true, flags: \JSON_THROW_ON_ERROR),
        );
    }
}
