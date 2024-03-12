<?php

declare(strict_types=1);

namespace AutoMapper\Tests\Transformer;

use AutoMapper\MapperMetadata;
use AutoMapper\Transformer\BuiltinTransformerFactory;
use AutoMapper\Transformer\ChainTransformerFactory;
use AutoMapper\Transformer\NullableTransformer;
use AutoMapper\Transformer\NullableTransformerFactory;
use PHPUnit\Framework\TestCase;
use Symfony\Component\PropertyInfo\Type;

class NullableTransformerFactoryTest extends TestCase
{
    private \ReflectionProperty $isTargetNullableProperty;

    protected function setUp(): void
    {
        $this->isTargetNullableProperty = (new \ReflectionClass(NullableTransformer::class))->getProperty('isTargetNullable');
        $this->isTargetNullableProperty->setAccessible(true);
    }

    public function testGetTransformer(): void
    {
        $chainFactory = new ChainTransformerFactory([new BuiltinTransformerFactory()]);
        $factory = new NullableTransformerFactory();
        $factory->setChainTransformerFactory($chainFactory);

        $mapperMetadata = $this->getMockBuilder(MapperMetadata::class)->disableOriginalConstructor()->getMock();

        $transformer = $factory->getTransformer([new Type('string', true)], [new Type('string')], $mapperMetadata);

        self::assertNotNull($transformer);
        self::assertInstanceOf(NullableTransformer::class, $transformer);
        self::assertFalse($this->isTargetNullableProperty->getValue($transformer));

        $transformer = $factory->getTransformer([new Type('string', true)], [new Type('string', true)], $mapperMetadata);

        self::assertNotNull($transformer);
        self::assertInstanceOf(NullableTransformer::class, $transformer);
        self::assertTrue($this->isTargetNullableProperty->getValue($transformer));

        $transformer = $factory->getTransformer([new Type('string', true)], [new Type('string'), new Type('int', true)], $mapperMetadata);

        self::assertNotNull($transformer);
        self::assertInstanceOf(NullableTransformer::class, $transformer);
        self::assertTrue($this->isTargetNullableProperty->getValue($transformer));

        $transformer = $factory->getTransformer([new Type('string', true)], [new Type('string'), new Type('int')], $mapperMetadata);

        self::assertNotNull($transformer);
        self::assertInstanceOf(NullableTransformer::class, $transformer);
        self::assertFalse($this->isTargetNullableProperty->getValue($transformer));
    }

    public function testNullTransformerIfSourceTypeNotNullable(): void
    {
        $chainFactory = new ChainTransformerFactory([new BuiltinTransformerFactory()]);
        $factory = new NullableTransformerFactory();
        $factory->setChainTransformerFactory($chainFactory);

        $mapperMetadata = $this->getMockBuilder(MapperMetadata::class)->disableOriginalConstructor()->getMock();

        $transformer = $factory->getTransformer([new Type('string')], [new Type('string')], $mapperMetadata);

        self::assertNull($transformer);
    }

    public function testNullTransformerIfMultipleSource(): void
    {
        $chainFactory = new ChainTransformerFactory([new BuiltinTransformerFactory()]);
        $factory = new NullableTransformerFactory();
        $factory->setChainTransformerFactory($chainFactory);

        $mapperMetadata = $this->getMockBuilder(MapperMetadata::class)->disableOriginalConstructor()->getMock();

        $transformer = $factory->getTransformer([new Type('string', true), new Type('string')], [new Type('string')], $mapperMetadata);

        self::assertNull($transformer);
    }
}
