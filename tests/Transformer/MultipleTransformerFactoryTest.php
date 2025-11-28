<?php

declare(strict_types=1);

namespace AutoMapper\Tests\Transformer;

use AutoMapper\Metadata\MapperMetadata;
use AutoMapper\Metadata\SourcePropertyMetadata;
use AutoMapper\Metadata\TargetPropertyMetadata;
use AutoMapper\Transformer\BuiltinTransformer;
use AutoMapper\Transformer\BuiltinTransformerFactory;
use AutoMapper\Transformer\ChainTransformerFactory;
use AutoMapper\Transformer\MultipleTransformer;
use AutoMapper\Transformer\MultipleTransformerFactory;
use PHPUnit\Framework\TestCase;
use Symfony\Component\TypeInfo\Type;

class MultipleTransformerFactoryTest extends TestCase
{
    public function testGetTransformer(): void
    {
        $chainFactory = new ChainTransformerFactory([new BuiltinTransformerFactory()]);
        $factory = new MultipleTransformerFactory();
        $factory->setChainTransformerFactory($chainFactory);

        $mapperMetadata = $this->getMockBuilder(MapperMetadata::class)->disableOriginalConstructor()->getMock();

        $sourceMapperMetadata = new SourcePropertyMetadata('foo', type: Type::union(Type::string(), Type::int()));
        $targetMapperMetadata = new TargetPropertyMetadata('foo');
        $transformer = $factory->getTransformer($sourceMapperMetadata, $targetMapperMetadata, $mapperMetadata);

        self::assertNotNull($transformer);
        self::assertInstanceOf(MultipleTransformer::class, $transformer);

        $sourceMapperMetadata = new SourcePropertyMetadata('foo', type: Type::union(Type::string(), Type::object(\DateInterval::class)));
        $targetMapperMetadata = new TargetPropertyMetadata('foo');
        $transformer = $factory->getTransformer($sourceMapperMetadata, $targetMapperMetadata, $mapperMetadata);

        self::assertNotNull($transformer);
        self::assertInstanceOf(BuiltinTransformer::class, $transformer);
    }

    public function testNoTransformerIfNoSubTransformer(): void
    {
        $chainFactory = new ChainTransformerFactory();
        $factory = new MultipleTransformerFactory();
        $factory->setChainTransformerFactory($chainFactory);

        $mapperMetadata = $this->getMockBuilder(MapperMetadata::class)->disableOriginalConstructor()->getMock();

        $sourceMapperMetadata = new SourcePropertyMetadata('foo', type: Type::union(Type::string(), Type::int()));
        $targetMapperMetadata = new TargetPropertyMetadata('foo');
        $transformer = $factory->getTransformer($sourceMapperMetadata, $targetMapperMetadata, $mapperMetadata);

        self::assertNull($transformer);
    }

    public function testNoTransformer(): void
    {
        $chainFactory = new ChainTransformerFactory([new BuiltinTransformerFactory()]);
        $factory = new MultipleTransformerFactory();
        $factory->setChainTransformerFactory($chainFactory);

        $mapperMetadata = $this->getMockBuilder(MapperMetadata::class)->disableOriginalConstructor()->getMock();

        $sourceMapperMetadata = new SourcePropertyMetadata('foo');
        $targetMapperMetadata = new TargetPropertyMetadata('foo');
        $transformer = $factory->getTransformer($sourceMapperMetadata, $targetMapperMetadata, $mapperMetadata);

        self::assertNull($transformer);

        $sourceMapperMetadata = new SourcePropertyMetadata('foo', type: Type::string());
        $targetMapperMetadata = new TargetPropertyMetadata('foo');
        $transformer = $factory->getTransformer($sourceMapperMetadata, $targetMapperMetadata, $mapperMetadata);

        self::assertNull($transformer);
    }
}
