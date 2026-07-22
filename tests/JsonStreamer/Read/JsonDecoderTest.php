<?php

declare(strict_types=1);

namespace AutoMapper\Tests\JsonStreamer\Read;

use AutoMapper\JsonStreamer\Read\JsonBuffer;
use AutoMapper\JsonStreamer\Read\JsonDecoder;
use AutoMapper\JsonStreamer\Read\JsonParser;
use AutoMapper\JsonStreamer\Read\LazyJsonList;
use AutoMapper\JsonStreamer\Read\LazyJsonObject;
use AutoMapper\Lazy\LazyMapInterface;
use PHPUnit\Framework\TestCase;

/**
 * @covers \AutoMapper\JsonStreamer\Read\JsonBuffer
 * @covers \AutoMapper\JsonStreamer\Read\JsonDecoder
 * @covers \AutoMapper\JsonStreamer\Read\JsonParser
 * @covers \AutoMapper\JsonStreamer\Read\LazyJsonList
 * @covers \AutoMapper\JsonStreamer\Read\LazyJsonObject
 */
class JsonDecoderTest extends TestCase
{
    public function testDecodeObjectIsALazyMap(): void
    {
        $object = JsonDecoder::decode('{"name":"yolo","age":13}');

        self::assertInstanceOf(LazyJsonObject::class, $object);
        self::assertInstanceOf(LazyMapInterface::class, $object);
        self::assertTrue($object->offsetExists('name'));
        self::assertFalse($object->offsetExists('missing'));
        self::assertSame('yolo', $object['name']);
        self::assertSame(13, $object['age']);
        self::assertNull($object['missing']);
    }

    public function testScalarTypesArePreserved(): void
    {
        $object = JsonDecoder::decode('{"i":-42,"f":20.1,"e":1.5e3,"t":true,"f2":false,"n":null,"s":"x"}');

        self::assertInstanceOf(LazyJsonObject::class, $object);
        self::assertSame(-42, $object['i']);
        self::assertSame(20.1, $object['f']);
        self::assertSame(1500.0, $object['e']);
        self::assertTrue($object['t']);
        self::assertFalse($object['f2']);
        self::assertNull($object['n']);
        self::assertSame('x', $object['s']);
    }

    public function testFieldsCanBeReadOutOfDocumentOrder(): void
    {
        $object = JsonDecoder::decode('{"a":1,"b":2,"c":3}');

        self::assertInstanceOf(LazyJsonObject::class, $object);
        // Read the last field first, then an earlier one: the buffer lets us come back.
        self::assertSame(3, $object['c']);
        self::assertSame(1, $object['a']);
        self::assertSame(2, $object['b']);
    }

    public function testStringEscapesAndUnicodeAreDecoded(): void
    {
        $object = JsonDecoder::decode('{"quote":"a\"b","unicode":"é","slash":"a\\/b","brace":"{not:a,field}"}');

        self::assertInstanceOf(LazyJsonObject::class, $object);
        self::assertSame('a"b', $object['quote']);
        self::assertSame('é', $object['unicode']);
        self::assertSame('a/b', $object['slash']);
        // Braces inside a string must not be mistaken for structure.
        self::assertSame('{not:a,field}', $object['brace']);
    }

    public function testNestedObjectStaysLazy(): void
    {
        $object = JsonDecoder::decode('{"user":{"city":"Toulon","zip":"83000"}}');

        self::assertInstanceOf(LazyJsonObject::class, $object);
        $nested = $object['user'];
        self::assertInstanceOf(LazyJsonObject::class, $nested);
        self::assertSame('Toulon', $nested['city']);
        self::assertSame('83000', $nested['zip']);
    }

    public function testListIsLazyAndReIterable(): void
    {
        $object = JsonDecoder::decode('{"items":[1,2,3]}');

        self::assertInstanceOf(LazyJsonObject::class, $object);
        $list = $object['items'];
        self::assertInstanceOf(LazyJsonList::class, $list);

        self::assertSame([1, 2, 3], iterator_to_array($list));
        // Re-iterable: iterating again yields the same values.
        self::assertSame([1, 2, 3], iterator_to_array($list));
    }

    public function testTopLevelListDecodesToLazyList(): void
    {
        $list = JsonDecoder::decode('[{"id":1},{"id":2}]');

        self::assertInstanceOf(LazyJsonList::class, $list);

        $ids = [];
        foreach ($list as $item) {
            self::assertInstanceOf(LazyJsonObject::class, $item);
            $ids[] = $item['id'];
        }

        self::assertSame([1, 2], $ids);
    }

    public function testEmptyObjectAndArray(): void
    {
        $object = JsonDecoder::decode('{"empty_obj":{},"empty_arr":[]}');

        self::assertInstanceOf(LazyJsonObject::class, $object);
        self::assertSame([], iterator_to_array($object['empty_obj']));
        self::assertSame([], iterator_to_array($object['empty_arr']));
    }

    public function testIterationPreservesKeyOrder(): void
    {
        $object = JsonDecoder::decode('{"b":1,"a":2,"c":3}');

        self::assertInstanceOf(LazyJsonObject::class, $object);
        self::assertSame(['b' => 1, 'a' => 2, 'c' => 3], iterator_to_array($object));
    }

    public function testIterationAfterPartialAccessStillYieldsEverything(): void
    {
        $object = JsonDecoder::decode('{"a":1,"b":2,"c":3}');

        self::assertInstanceOf(LazyJsonObject::class, $object);
        self::assertSame(2, $object['b']); // partially scan first
        self::assertSame(['a' => 1, 'b' => 2, 'c' => 3], iterator_to_array($object));
    }

    public function testWhitespaceIsTolerated(): void
    {
        $object = JsonDecoder::decode("{\n  \"a\" : 1 ,\n  \"b\" : [ 1 , 2 ]\n}");

        self::assertInstanceOf(LazyJsonObject::class, $object);
        self::assertSame(1, $object['a']);
        self::assertSame([1, 2], iterator_to_array($object['b']));
    }

    public function testJsonSerializeRoundTrips(): void
    {
        $json = '{"name":"yolo","address":{"city":"Toulon"},"tags":["a","b"]}';
        $object = JsonDecoder::decode($json);

        self::assertInstanceOf(LazyJsonObject::class, $object);
        self::assertJsonStringEqualsJsonString($json, json_encode($object));
    }

    public function testReadOnly(): void
    {
        $object = JsonDecoder::decode('{"a":1}');

        self::assertInstanceOf(LazyJsonObject::class, $object);
        $this->expectException(\LogicException::class);
        $object['a'] = 2;
    }

    public function testDecodesFromANonRewoundChunkedStream(): void
    {
        $json = '{"name":"yolo","address":{"city":"Toulon"},"items":[1,2,3]}';
        $stream = fopen('php://temp', 'r+');
        fwrite($stream, $json);
        rewind($stream);

        // Tiny chunk size to force many reads and exercise the on-demand fill path.
        $buffer = new JsonBuffer($stream, chunkSize: 4);
        $object = JsonParser::parseValue($buffer, JsonParser::skipWhitespace($buffer, 0));

        self::assertInstanceOf(LazyJsonObject::class, $object);
        self::assertSame('yolo', $object['name']);
        self::assertSame('Toulon', $object['address']['city']);
        self::assertSame([1, 2, 3], iterator_to_array($object['items']));
    }
}
