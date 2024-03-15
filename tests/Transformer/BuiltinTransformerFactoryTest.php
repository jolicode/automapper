<?php

declare(strict_types=1);

namespace AutoMapper\Tests\Transformer;

use AutoMapper\Metadata\MapperMetadata;
use AutoMapper\Metadata\SourcePropertyMetadata;
use AutoMapper\Metadata\TargetPropertyMetadata;
use AutoMapper\Transformer\BuiltinTransformer;
use AutoMapper\Transformer\BuiltinTransformerFactory;
use PHPUnit\Framework\TestCase;
use Symfony\Component\PropertyInfo\Type;

class BuiltinTransformerFactoryTest extends TestCase
{
    public function testGetTransformer(): void
    {
        $factory = new BuiltinTransformerFactory();
        $mapperMetadata = $this->getMockBuilder(MapperMetadata::class)->disableOriginalConstructor()->getMock();

        $sourceMapperMetadata = new SourcePropertyMetadata([new Type('string')], 'foo');
        $targetMapperMetadata = new TargetPropertyMetadata([new Type('string')], 'foo');
        $transformer = $factory->getTransformer($sourceMapperMetadata, $targetMapperMetadata, $mapperMetadata);

        self::assertInstanceOf(BuiltinTransformer::class, $transformer);

        $sourceMapperMetadata = new SourcePropertyMetadata([new Type('bool')], 'foo');
        $targetMapperMetadata = new TargetPropertyMetadata([new Type('string')], 'foo');
        $transformer = $factory->getTransformer($sourceMapperMetadata, $targetMapperMetadata, $mapperMetadata);

        self::assertInstanceOf(BuiltinTransformer::class, $transformer);
    }

    public function testNoTransformer(): void
    {
        $factory = new BuiltinTransformerFactory();
        $mapperMetadata = $this->getMockBuilder(MapperMetadata::class)->disableOriginalConstructor()->getMock();

        $sourceMapperMetadata = new SourcePropertyMetadata([], 'foo');
        $targetMapperMetadata = new TargetPropertyMetadata([new Type('string')], 'foo');
        $transformer = $factory->getTransformer($sourceMapperMetadata, $targetMapperMetadata, $mapperMetadata);

        self::assertNull($transformer);

        $sourceMapperMetadata = new SourcePropertyMetadata([new Type('string'), new Type('string')], 'foo');
        $targetMapperMetadata = new TargetPropertyMetadata([new Type('string')], 'foo');
        $transformer = $factory->getTransformer($sourceMapperMetadata, $targetMapperMetadata, $mapperMetadata);

        self::assertNull($transformer);

        $sourceMapperMetadata = new SourcePropertyMetadata([new Type('array')], 'foo');
        $targetMapperMetadata = new TargetPropertyMetadata([new Type('string')], 'foo');
        $transformer = $factory->getTransformer($sourceMapperMetadata, $targetMapperMetadata, $mapperMetadata);

        self::assertNull($transformer);

        $sourceMapperMetadata = new SourcePropertyMetadata([new Type('object')], 'foo');
        $targetMapperMetadata = new TargetPropertyMetadata([new Type('string')], 'foo');
        $transformer = $factory->getTransformer($sourceMapperMetadata, $targetMapperMetadata, $mapperMetadata);

        self::assertNull($transformer);
    }
}
