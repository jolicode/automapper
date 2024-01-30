<?php

declare(strict_types=1);

namespace AutoMapper\Tests\Transformer;

use AutoMapper\AutoMapperRegistryInterface;
use AutoMapper\MapperMetadata\MapperMetadata;
use AutoMapper\Transformer\ObjectTransformer;
use AutoMapper\Transformer\ObjectTransformerFactory;
use PHPUnit\Framework\TestCase;
use Symfony\Component\PropertyInfo\Type;

class ObjectTransformerFactoryTest extends TestCase
{
    public function testGetTransformer(): void
    {
        $autoMapperRegistry = $this->getMockBuilder(AutoMapperRegistryInterface::class)->getMock();
        $mapperMetadata = $this->getMockBuilder(MapperMetadata::class)->disableOriginalConstructor()->getMock();
        $factory = new ObjectTransformerFactory($autoMapperRegistry);

        $autoMapperRegistry
            ->expects($this->any())
            ->method('hasMapper')
            ->willReturn(true)
        ;

        $transformer = $factory->getTransformer([new Type('object', false, \stdClass::class)], [new Type('object', false, \stdClass::class)], $mapperMetadata);

        self::assertNotNull($transformer);
        self::assertInstanceOf(ObjectTransformer::class, $transformer);

        $transformer = $factory->getTransformer([new Type('array')], [new Type('object', false, \stdClass::class)], $mapperMetadata);

        self::assertNotNull($transformer);
        self::assertInstanceOf(ObjectTransformer::class, $transformer);

        $transformer = $factory->getTransformer([new Type('object', false, \stdClass::class)], [new Type('array')], $mapperMetadata);

        self::assertNotNull($transformer);
        self::assertInstanceOf(ObjectTransformer::class, $transformer);
    }

    public function testNoTransformer(): void
    {
        $autoMapperRegistry = $this->getMockBuilder(AutoMapperRegistryInterface::class)->getMock();
        $mapperMetadata = $this->getMockBuilder(MapperMetadata::class)->disableOriginalConstructor()->getMock();
        $factory = new ObjectTransformerFactory($autoMapperRegistry);

        $transformer = $factory->getTransformer([], [], $mapperMetadata);

        self::assertNull($transformer);

        $transformer = $factory->getTransformer([new Type('object')], [], $mapperMetadata);

        self::assertNull($transformer);

        $transformer = $factory->getTransformer([], [new Type('object')], $mapperMetadata);

        self::assertNull($transformer);

        $transformer = $factory->getTransformer([new Type('object'), new Type('object')], [new Type('object')], $mapperMetadata);

        self::assertNull($transformer);

        $transformer = $factory->getTransformer([new Type('object')], [new Type('object'), new Type('object')], $mapperMetadata);

        self::assertNull($transformer);
    }
}
