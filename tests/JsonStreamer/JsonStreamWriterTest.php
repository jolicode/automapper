<?php

namespace AutoMapper\Tests\JsonStreamer;

use AutoMapper\Tests\AutoMapperBuilder;
use AutoMapper\Tests\AutoMapperTestCase;
use AutoMapper\JsonStreamer\JsonStreamWriter;
use Symfony\Component\VarDumper\Dumper\CliDumper;
use Symfony\Component\VarDumper\Test\VarDumperTestTrait;
use AutoMapper\Tests\Fixtures;
use Symfony\Component\JsonStreamer\JsonStreamWriter as FallbackJsonStreamWriter;
use Symfony\Component\TypeInfo\Type;

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
                \Throwable::class => function (\Throwable $e) {
                    return [
                        "class" => $e::class,
                        "message" => $e->getMessage(),
                    ];
                },
            ],
            CliDumper::DUMP_LIGHT_ARRAY,
        );
    }

    public function testJsonStreamerMap(): void
    {
        $this->autoMapper = AutoMapperBuilder::buildAutoMapper(
            mapPrivatePropertiesAndMethod: true,
        );

        $address = new Fixtures\Address();
        $address->setCity("Toulon");
        $user = new Fixtures\User(1, "yolo", "13");
        $user->address = $address;
        $user->addresses[] = $address;
        $user->money = 20.1;

        $jsonStreamWriter = new JsonStreamWriter(
            $this->autoMapper,
            FallbackJsonStreamWriter::create(),
        );

        $json = $jsonStreamWriter->write(
            $user,
            Type::object(Fixtures\User::class),
        );

        $data = json_decode($json, true);
        $user2 = $this->autoMapper->map($data, Fixtures\User::class);

        $this->assertEquals($user, $user2);
    }
}
