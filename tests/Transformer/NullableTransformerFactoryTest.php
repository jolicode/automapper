<?php

declare(strict_types=1);

namespace AutoMapper\Tests\Transformer;

use AutoMapper\Metadata\MapperMetadata;
use AutoMapper\Metadata\SourcePropertyMetadata;
use AutoMapper\Metadata\TargetPropertyMetadata;
use AutoMapper\Transformer\BuiltinTransformerFactory;
use AutoMapper\Transformer\ChainTransformerFactory;
use AutoMapper\Transformer\NullableTransformer;
use AutoMapper\Transformer\NullableTransformerFactory;
use PHPUnit\Framework\TestCase;
use Symfony\Component\TypeInfo\Type;

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

        $sourceMapperMetadata = new SourcePropertyMetadata('foo', type: Type::nullable(Type::string()));
        $targetMapperMetadata = new TargetPropertyMetadata('foo', type: Type::string());
        $transformer = $factory->getTransformer($sourceMapperMetadata, $targetMapperMetadata, $mapperMetadata);

        self::assertNotNull($transformer);
        self::assertInstanceOf(NullableTransformer::class, $transformer);
        self::assertFalse($this->isTargetNullableProperty->getValue($transformer));

        $sourceMapperMetadata = new SourcePropertyMetadata('foo', type: Type::nullable(Type::string()));
        $targetMapperMetadata = new TargetPropertyMetadata('foo', type: Type::nullable(Type::string()));
        $transformer = $factory->getTransformer($sourceMapperMetadata, $targetMapperMetadata, $mapperMetadata);

        self::assertNotNull($transformer);
        self::assertInstanceOf(NullableTransformer::class, $transformer);
        self::assertTrue($this->isTargetNullableProperty->getValue($transformer));

        $sourceMapperMetadata = new SourcePropertyMetadata('foo', type: Type::nullable(Type::string()));
        $targetMapperMetadata = new TargetPropertyMetadata('foo', type: Type::union(Type::string(), Type::nullable(Type::int())));
        $transformer = $factory->getTransformer($sourceMapperMetadata, $targetMapperMetadata, $mapperMetadata);

        self::assertNotNull($transformer);
        self::assertInstanceOf(NullableTransformer::class, $transformer);
        self::assertTrue($this->isTargetNullableProperty->getValue($transformer));

        $sourceMapperMetadata = new SourcePropertyMetadata('foo', type: Type::nullable(Type::string()));
        $targetMapperMetadata = new TargetPropertyMetadata('foo', type: Type::union(Type::string(), Type::int()));
        $transformer = $factory->getTransformer($sourceMapperMetadata, $targetMapperMetadata, $mapperMetadata);

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

        $sourceMapperMetadata = new SourcePropertyMetadata('foo', type: Type::string());
        $targetMapperMetadata = new TargetPropertyMetadata('foo', type: Type::string());
        $transformer = $factory->getTransformer($sourceMapperMetadata, $targetMapperMetadata, $mapperMetadata);

        self::assertNull($transformer);
    }

    public function testNullTransformerIfMultipleSource(): void
    {
        $chainFactory = new ChainTransformerFactory([new BuiltinTransformerFactory()]);
        $factory = new NullableTransformerFactory();
        $factory->setChainTransformerFactory($chainFactory);

        $mapperMetadata = $this->getMockBuilder(MapperMetadata::class)->disableOriginalConstructor()->getMock();

        $sourceMapperMetadata = new SourcePropertyMetadata('foo', type: Type::union(Type::nullable(Type::string()), Type::string()));
        $targetMapperMetadata = new TargetPropertyMetadata('foo', type: Type::string());
        $transformer = $factory->getTransformer($sourceMapperMetadata, $targetMapperMetadata, $mapperMetadata);

        self::assertNull($transformer);
    }
}
