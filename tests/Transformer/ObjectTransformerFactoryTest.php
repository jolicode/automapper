<?php

declare(strict_types=1);

namespace AutoMapper\Tests\Transformer;

use AutoMapper\Metadata\MapperMetadata;
use AutoMapper\Metadata\SourcePropertyMetadata;
use AutoMapper\Metadata\TargetPropertyMetadata;
use AutoMapper\Transformer\ObjectTransformer;
use AutoMapper\Transformer\ObjectTransformerFactory;
use PHPUnit\Framework\TestCase;
use Symfony\Component\PropertyInfo\Type;

class ObjectTransformerFactoryTest extends TestCase
{
    public function testGetTransformer(): void
    {
        $mapperMetadata = $this->getMockBuilder(MapperMetadata::class)->disableOriginalConstructor()->getMock();
        $factory = new ObjectTransformerFactory();

        $sourceMapperMetadata = new SourcePropertyMetadata([new Type('object', false, \stdClass::class)], 'foo');
        $targetMapperMetadata = new TargetPropertyMetadata([new Type('object', false, \stdClass::class)], 'foo');
        $transformer = $factory->getTransformer($sourceMapperMetadata, $targetMapperMetadata, $mapperMetadata);

        self::assertNotNull($transformer);
        self::assertInstanceOf(ObjectTransformer::class, $transformer);

        $sourceMapperMetadata = new SourcePropertyMetadata([new Type('array')], 'foo');
        $targetMapperMetadata = new TargetPropertyMetadata([new Type('object', false, \stdClass::class)], 'foo');
        $transformer = $factory->getTransformer($sourceMapperMetadata, $targetMapperMetadata, $mapperMetadata);

        self::assertNotNull($transformer);
        self::assertInstanceOf(ObjectTransformer::class, $transformer);

        $sourceMapperMetadata = new SourcePropertyMetadata([new Type('object', false, \stdClass::class)], 'foo');
        $targetMapperMetadata = new TargetPropertyMetadata([new Type('array')], 'foo');
        $transformer = $factory->getTransformer($sourceMapperMetadata, $targetMapperMetadata, $mapperMetadata);

        self::assertNotNull($transformer);
        self::assertInstanceOf(ObjectTransformer::class, $transformer);
    }

    public function testNoTransformer(): void
    {
        $mapperMetadata = $this->getMockBuilder(MapperMetadata::class)->disableOriginalConstructor()->getMock();
        $factory = new ObjectTransformerFactory();

        $sourceMapperMetadata = new SourcePropertyMetadata([], 'foo');
        $targetMapperMetadata = new TargetPropertyMetadata([], 'foo');
        $transformer = $factory->getTransformer($sourceMapperMetadata, $targetMapperMetadata, $mapperMetadata);

        self::assertNull($transformer);

        $sourceMapperMetadata = new SourcePropertyMetadata([new Type('object')], 'foo');
        $targetMapperMetadata = new TargetPropertyMetadata([], 'foo');
        $transformer = $factory->getTransformer($sourceMapperMetadata, $targetMapperMetadata, $mapperMetadata);

        self::assertNull($transformer);

        $sourceMapperMetadata = new SourcePropertyMetadata([], 'foo');
        $targetMapperMetadata = new TargetPropertyMetadata([new Type('object')], 'foo');
        $transformer = $factory->getTransformer($sourceMapperMetadata, $targetMapperMetadata, $mapperMetadata);

        self::assertNull($transformer);

        $sourceMapperMetadata = new SourcePropertyMetadata([new Type('object'), new Type('object')], 'foo');
        $targetMapperMetadata = new TargetPropertyMetadata([new Type('object')], 'foo');
        $transformer = $factory->getTransformer($sourceMapperMetadata, $targetMapperMetadata, $mapperMetadata);

        self::assertNull($transformer);

        $sourceMapperMetadata = new SourcePropertyMetadata([new Type('object')], 'foo');
        $targetMapperMetadata = new TargetPropertyMetadata([new Type('object'), new Type('object')], 'foo');
        $transformer = $factory->getTransformer($sourceMapperMetadata, $targetMapperMetadata, $mapperMetadata);

        self::assertNull($transformer);
    }
}
