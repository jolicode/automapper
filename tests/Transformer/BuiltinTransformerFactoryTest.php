<?php

declare(strict_types=1);

namespace AutoMapper\Tests\Transformer;

use AutoMapper\Metadata\MapperMetadata;
use AutoMapper\Metadata\SourcePropertyMetadata;
use AutoMapper\Metadata\TargetPropertyMetadata;
use AutoMapper\Metadata\TypesMatching;
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

        $sourceMapperMetadata = new SourcePropertyMetadata('foo');
        $targetMapperMetadata = new TargetPropertyMetadata('foo');
        $types = TypesMatching::fromSourceAndTargetTypes([new Type('string')], [new Type('string')], );
        $transformer = $factory->getTransformer($types, $sourceMapperMetadata, $targetMapperMetadata, $mapperMetadata);

        self::assertInstanceOf(BuiltinTransformer::class, $transformer);

        $sourceMapperMetadata = new SourcePropertyMetadata('foo');
        $targetMapperMetadata = new TargetPropertyMetadata('foo');
        $types = TypesMatching::fromSourceAndTargetTypes([new Type('bool')], [new Type('string')], );
        $transformer = $factory->getTransformer($types, $sourceMapperMetadata, $targetMapperMetadata, $mapperMetadata);

        self::assertInstanceOf(BuiltinTransformer::class, $transformer);
    }

    public function testNoTransformer(): void
    {
        $factory = new BuiltinTransformerFactory();
        $mapperMetadata = $this->getMockBuilder(MapperMetadata::class)->disableOriginalConstructor()->getMock();

        $sourceMapperMetadata = new SourcePropertyMetadata('foo');
        $targetMapperMetadata = new TargetPropertyMetadata('foo');
        $types = TypesMatching::fromSourceAndTargetTypes([], [new Type('string')], );
        $transformer = $factory->getTransformer($types, $sourceMapperMetadata, $targetMapperMetadata, $mapperMetadata);

        self::assertNull($transformer);

        $sourceMapperMetadata = new SourcePropertyMetadata('foo');
        $targetMapperMetadata = new TargetPropertyMetadata('foo');
        $types = TypesMatching::fromSourceAndTargetTypes([new Type('string'), new Type('string')], [new Type('string')], );
        $transformer = $factory->getTransformer($types, $sourceMapperMetadata, $targetMapperMetadata, $mapperMetadata);

        self::assertNull($transformer);

        $sourceMapperMetadata = new SourcePropertyMetadata('foo');
        $targetMapperMetadata = new TargetPropertyMetadata('foo');
        $types = TypesMatching::fromSourceAndTargetTypes([new Type('array')], [new Type('string')], );
        $transformer = $factory->getTransformer($types, $sourceMapperMetadata, $targetMapperMetadata, $mapperMetadata);

        self::assertNull($transformer);

        $sourceMapperMetadata = new SourcePropertyMetadata('foo');
        $targetMapperMetadata = new TargetPropertyMetadata('foo');
        $types = TypesMatching::fromSourceAndTargetTypes([new Type('object')], [new Type('string')], );
        $transformer = $factory->getTransformer($types, $sourceMapperMetadata, $targetMapperMetadata, $mapperMetadata);

        self::assertNull($transformer);
    }
}
