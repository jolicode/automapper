<?php

declare(strict_types=1);

namespace AutoMapper\Tests\JsonStreamer;

use AutoMapper\Configuration;
use AutoMapper\JsonStreamer\JsonStreamReader;
use AutoMapper\JsonStreamer\JsonStreamWriter;
use AutoMapper\Metadata\MetadataRegistry;
use AutoMapper\Tests\AutoMapperBuilder;
use AutoMapper\Tests\AutoMapperTestCase;
use AutoMapper\Tests\Fixtures;
use Symfony\Component\JsonStreamer\JsonStreamReader as FallbackJsonStreamReader;
use Symfony\Component\JsonStreamer\JsonStreamWriter as FallbackJsonStreamWriter;
use Symfony\Component\TypeInfo\Type;

/**
 * When a metadata registry is given, only the registered types go through the AutoMapper, the
 * others being delegated to the underlying Symfony implementation.
 *
 * @covers \AutoMapper\JsonStreamer\JsonStreamReader
 * @covers \AutoMapper\JsonStreamer\JsonStreamWriter
 */
class RegistryAwareTest extends AutoMapperTestCase
{
    /**
     * @return resource
     */
    private function stream(string $json)
    {
        $stream = fopen('php://memory', 'r+');
        fwrite($stream, $json);
        rewind($stream);

        return $stream;
    }

    private function registry(): MetadataRegistry
    {
        // Fixtures\User is registered, Fixtures\Address is not. The registration is symmetric with
        // the mappers actually used: `json` -> class when reading, class -> `json` when writing.
        $registry = new MetadataRegistry(new Configuration());
        $registry->register('json', Fixtures\User::class);
        $registry->register(Fixtures\User::class, 'json');

        return $registry;
    }

    public function testRegisteredTypeIsReadByTheAutoMapper(): void
    {
        $autoMapper = AutoMapperBuilder::buildAutoMapper(
            mapPrivatePropertiesAndMethod: true,
            classPrefix: 'JsonStreamerRegistryAware_',
        );
        $reader = new JsonStreamReader($autoMapper, FallbackJsonStreamReader::create(), $this->registry());

        $user = $reader->read(
            $this->stream('{"id":1,"name":"yolo","age":"13"}'),
            Type::object(Fixtures\User::class),
        );

        self::assertInstanceOf(Fixtures\User::class, $user);
        // private property, only the AutoMapper can hydrate it
        self::assertSame(1, $user->getId());
    }

    public function testUnregisteredTypeFallsBackToSymfony(): void
    {
        $autoMapper = AutoMapperBuilder::buildAutoMapper(
            mapPrivatePropertiesAndMethod: true,
            classPrefix: 'JsonStreamerRegistryAware_',
        );
        $reader = new JsonStreamReader($autoMapper, FallbackJsonStreamReader::create(), $this->registry());

        $address = $reader->read($this->stream('{"city":"Toulon"}'), Type::object(Fixtures\Address::class));

        // the Symfony reader still builds the object, but it cannot fill the private property the
        // AutoMapper would have mapped: this proves the fallback was used
        self::assertInstanceOf(Fixtures\Address::class, $address);
        self::assertNull((new \ReflectionProperty(Fixtures\Address::class, 'city'))->getValue($address));
    }

    public function testRegisteredTypeIsWrittenByTheAutoMapper(): void
    {
        $autoMapper = AutoMapperBuilder::buildAutoMapper(
            mapPrivatePropertiesAndMethod: true,
            classPrefix: 'JsonStreamerRegistryAware_',
        );
        $writer = new JsonStreamWriter($autoMapper, FallbackJsonStreamWriter::create(), $this->registry());

        $json = (string) $writer->write(new Fixtures\User(1, 'yolo', '13'), Type::object(Fixtures\User::class));

        // the private "id" property is only exposed through the AutoMapper mapping
        self::assertSame(1, json_decode($json, true)['id']);
    }

    public function testUnregisteredTypeIsWrittenBySymfony(): void
    {
        $autoMapper = AutoMapperBuilder::buildAutoMapper(
            mapPrivatePropertiesAndMethod: true,
            classPrefix: 'JsonStreamerRegistryAware_',
        );
        $writer = new JsonStreamWriter($autoMapper, FallbackJsonStreamWriter::create(), $this->registry());

        $address = new Fixtures\Address();
        $address->setCity('Toulon');

        // Symfony only writes public properties, Address exposes none: an empty object proves the
        // fallback was used instead of the AutoMapper mapping
        self::assertSame('{}', (string) $writer->write($address, Type::object(Fixtures\Address::class)));
    }

    public function testNoRegistryHandlesEveryType(): void
    {
        $autoMapper = AutoMapperBuilder::buildAutoMapper(
            mapPrivatePropertiesAndMethod: true,
            classPrefix: 'JsonStreamerRegistryAware_',
        );
        $writer = new JsonStreamWriter($autoMapper, FallbackJsonStreamWriter::create());

        $address = new Fixtures\Address();
        $address->setCity('Toulon');

        // without a registry every type goes through the AutoMapper, private properties included
        self::assertSame(['city' => 'Toulon'], json_decode((string) $writer->write($address, Type::object(Fixtures\Address::class)), true));
    }
}
