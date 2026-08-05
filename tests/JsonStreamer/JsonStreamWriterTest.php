<?php

declare(strict_types=1);

namespace AutoMapper\Tests\JsonStreamer;

use AutoMapper\Exception\InvalidMappingException;
use AutoMapper\JsonStreamer\JsonStreamWriter;
use AutoMapper\Tests\AutoMapperBuilder;
use AutoMapper\Tests\AutoMapperTestCase;
use AutoMapper\Tests\Fixtures;
use Symfony\Component\JsonStreamer\JsonStreamWriter as FallbackJsonStreamWriter;
use Symfony\Component\TypeInfo\Type;
use Symfony\Component\VarDumper\Dumper\CliDumper;
use Symfony\Component\VarDumper\Test\VarDumperTestTrait;

/**
 * @covers \AutoMapper\JsonStreamer\JsonStreamWriter
 */
class JsonStreamWriterTest extends AutoMapperTestCase
{
    use VarDumperTestTrait;

    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpVarDumper(
            [
                \Throwable::class => static function (\Throwable $e) {
                    return [
                        'class' => $e::class,
                        'message' => $e->getMessage(),
                    ];
                },
            ],
            CliDumper::DUMP_LIGHT_ARRAY,
        );
    }

    public function testJsonStreamerMap(): void
    {
        $autoMapper = AutoMapperBuilder::buildAutoMapper(
            mapPrivatePropertiesAndMethod: true,
            classPrefix: 'JsonStreamerPrivate_'
        );

        $address = new Fixtures\Address();
        $address->setCity('Toulon');
        $user = new Fixtures\User(1, 'yolo', '13');
        $user->address = $address;
        $user->addresses[] = $address;
        $user->money = 20.1;

        $jsonStreamWriter = new JsonStreamWriter(
            $autoMapper,
            FallbackJsonStreamWriter::create(),
        );

        $json = $jsonStreamWriter->write(
            $user,
            Type::object(Fixtures\User::class),
        );

        $data = json_decode((string) $json, true);
        $user2 = $autoMapper->map($data, Fixtures\User::class);

        $this->assertEquals($user, $user2);
    }

    public function testGetJsonMapperStreamsThroughMapMethod(): void
    {
        $autoMapper = AutoMapperBuilder::buildAutoMapper(
            mapPrivatePropertiesAndMethod: true,
            classPrefix: 'JsonStreamerPrivate_'
        );

        $address = new Fixtures\Address();
        $address->setCity('Toulon');
        $user = new Fixtures\User(1, 'yolo', '13');
        $user->address = $address;
        $user->addresses[] = $address;
        $user->money = 20.1;

        // `json` is a serialization target, it is not reachable through AutoMapperInterface::map()
        try {
            $autoMapper->map($user, 'json');
            self::fail('Mapping to the "json" target should not be allowed.');
        } catch (InvalidMappingException $e) {
            self::assertStringContainsString('json', $e->getMessage());
        }

        // Only the mapper itself, as used by the JsonStreamer integration, exposes the stream.
        $stream = $autoMapper->getMapper(Fixtures\User::class, 'json')->map($user);

        self::assertInstanceOf(\Traversable::class, $stream, 'A json mapper streams its result.');

        $json = '';
        foreach ($stream as $chunk) {
            self::assertIsString($chunk);
            $json .= $chunk;
        }

        $data = json_decode($json, true, flags: \JSON_THROW_ON_ERROR);
        self::assertSame('Toulon', $data['address']['city']);
        self::assertCount(1, $data['addresses']);

        // And it round-trips back to the original object.
        $user2 = $autoMapper->map($data, Fixtures\User::class);
        self::assertEquals($user, $user2);
    }
}
