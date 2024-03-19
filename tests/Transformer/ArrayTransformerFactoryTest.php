<?php

declare(strict_types=1);

namespace AutoMapper\Tests\Transformer;

use AutoMapper\Metadata\MapperMetadata;
use AutoMapper\Metadata\SourcePropertyMetadata;
use AutoMapper\Metadata\TargetPropertyMetadata;
use AutoMapper\Metadata\TypesMatching;
use AutoMapper\Transformer\ArrayTransformerFactory;
use AutoMapper\Transformer\ChainTransformerFactory;
use AutoMapper\Transformer\DictionaryTransformer;
use PHPUnit\Framework\TestCase;
use Symfony\Component\PropertyInfo\Type;

class ArrayTransformerFactoryTest extends TestCase
{
    public function testGetTransformer(): void
    {
        $chainFactory = new ChainTransformerFactory();
        $factory = new ArrayTransformerFactory();
        $factory->setChainTransformerFactory($chainFactory);
        $mapperMetadata = $this->getMockBuilder(MapperMetadata::class)->disableOriginalConstructor()->getMock();

        $sourceMapperMetadata = new SourcePropertyMetadata('foo');
        $targetMapperMetadata = new TargetPropertyMetadata('foo');
        $types = TypesMatching::fromSourceAndTargetTypes([new Type('array', false, null, true)], [new Type('array', false, null, true)], );
        $transformer = $factory->getTransformer($types, $sourceMapperMetadata, $targetMapperMetadata, $mapperMetadata);

        self::assertInstanceOf(DictionaryTransformer::class, $transformer);
    }

    public function testNoTransformerTargetNoCollection(): void
    {
        $chainFactory = new ChainTransformerFactory();
        $factory = new ArrayTransformerFactory();
        $factory->setChainTransformerFactory($chainFactory);
        $mapperMetadata = $this->getMockBuilder(MapperMetadata::class)->disableOriginalConstructor()->getMock();

        $sourceMapperMetadata = new SourcePropertyMetadata('foo');
        $targetMapperMetadata = new TargetPropertyMetadata('foo');
        $types = TypesMatching::fromSourceAndTargetTypes([new Type('array', false, null, true)], [new Type('string')], );
        $transformer = $factory->getTransformer($types, $sourceMapperMetadata, $targetMapperMetadata, $mapperMetadata);

        self::assertNull($transformer);
    }

    public function testNoTransformerSourceNoCollection(): void
    {
        $chainFactory = new ChainTransformerFactory();
        $factory = new ArrayTransformerFactory();
        $factory->setChainTransformerFactory($chainFactory);
        $mapperMetadata = $this->getMockBuilder(MapperMetadata::class)->disableOriginalConstructor()->getMock();

        $sourceMapperMetadata = new SourcePropertyMetadata('foo');
        $targetMapperMetadata = new TargetPropertyMetadata('foo');
        $types = TypesMatching::fromSourceAndTargetTypes([new Type('string')], [new Type('array', false, null, true)], );
        $transformer = $factory->getTransformer($types, $sourceMapperMetadata, $targetMapperMetadata, $mapperMetadata);

        self::assertNull($transformer);
    }

    public function testNoTransformerIfNoSubTypeTransformerNoCollection(): void
    {
        $chainFactory = new ChainTransformerFactory();
        $factory = new ArrayTransformerFactory();
        $factory->setChainTransformerFactory($chainFactory);
        $mapperMetadata = $this->getMockBuilder(MapperMetadata::class)->disableOriginalConstructor()->getMock();

        $stringType = new Type('string');

        $sourceMapperMetadata = new SourcePropertyMetadata('foo');
        $targetMapperMetadata = new TargetPropertyMetadata('foo');
        $types = TypesMatching::fromSourceAndTargetTypes([new Type('array', false, null, true, null, $stringType)], [new Type('array', false, null, true, null, $stringType)], );
        $transformer = $factory->getTransformer($types, $sourceMapperMetadata, $targetMapperMetadata, $mapperMetadata);

        self::assertNull($transformer);
    }
}
