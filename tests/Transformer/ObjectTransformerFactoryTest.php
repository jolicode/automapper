<?php

declare(strict_types=1);

namespace AutoMapper\Tests\Transformer;

use AutoMapper\Metadata\MapperMetadata;
use AutoMapper\Metadata\SourcePropertyMetadata;
use AutoMapper\Metadata\TargetPropertyMetadata;
use AutoMapper\Metadata\TypesMatching;
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

        $sourceMapperMetadata = new SourcePropertyMetadata('foo');
        $targetMapperMetadata = new TargetPropertyMetadata('foo');
        $types = TypesMatching::fromSourceAndTargetTypes([new Type('object', false, \stdClass::class)], [new Type('object', false, \stdClass::class)], );
        $transformer = $factory->getTransformer($types, $sourceMapperMetadata, $targetMapperMetadata, $mapperMetadata);

        self::assertNotNull($transformer);
        self::assertInstanceOf(ObjectTransformer::class, $transformer);

        $sourceMapperMetadata = new SourcePropertyMetadata('foo');
        $targetMapperMetadata = new TargetPropertyMetadata('foo');
        $types = TypesMatching::fromSourceAndTargetTypes([new Type('array')], [new Type('object', false, \stdClass::class)], );
        $transformer = $factory->getTransformer($types, $sourceMapperMetadata, $targetMapperMetadata, $mapperMetadata);

        self::assertNotNull($transformer);
        self::assertInstanceOf(ObjectTransformer::class, $transformer);

        $sourceMapperMetadata = new SourcePropertyMetadata('foo');
        $targetMapperMetadata = new TargetPropertyMetadata('foo');
        $types = TypesMatching::fromSourceAndTargetTypes([new Type('object', false, \stdClass::class)], [new Type('array')], );
        $transformer = $factory->getTransformer($types, $sourceMapperMetadata, $targetMapperMetadata, $mapperMetadata);

        self::assertNotNull($transformer);
        self::assertInstanceOf(ObjectTransformer::class, $transformer);
    }

    public function testNoTransformer(): void
    {
        $mapperMetadata = $this->getMockBuilder(MapperMetadata::class)->disableOriginalConstructor()->getMock();
        $factory = new ObjectTransformerFactory();

        $sourceMapperMetadata = new SourcePropertyMetadata('foo');
        $targetMapperMetadata = new TargetPropertyMetadata('foo');
        $types = TypesMatching::fromSourceAndTargetTypes([], []);
        $transformer = $factory->getTransformer($types, $sourceMapperMetadata, $targetMapperMetadata, $mapperMetadata);

        self::assertNull($transformer);

        $sourceMapperMetadata = new SourcePropertyMetadata('foo');
        $targetMapperMetadata = new TargetPropertyMetadata('foo');
        $types = TypesMatching::fromSourceAndTargetTypes([new Type('object')], []);
        $transformer = $factory->getTransformer($types, $sourceMapperMetadata, $targetMapperMetadata, $mapperMetadata);

        self::assertNull($transformer);

        $sourceMapperMetadata = new SourcePropertyMetadata('foo');
        $targetMapperMetadata = new TargetPropertyMetadata('foo');
        $types = TypesMatching::fromSourceAndTargetTypes([], [new Type('object')], );
        $transformer = $factory->getTransformer($types, $sourceMapperMetadata, $targetMapperMetadata, $mapperMetadata);

        self::assertNull($transformer);

        $sourceMapperMetadata = new SourcePropertyMetadata('foo');
        $targetMapperMetadata = new TargetPropertyMetadata('foo');
        $types = TypesMatching::fromSourceAndTargetTypes([new Type('object'), new Type('object')], [new Type('object')], );
        $transformer = $factory->getTransformer($types, $sourceMapperMetadata, $targetMapperMetadata, $mapperMetadata);

        self::assertNull($transformer);

        $sourceMapperMetadata = new SourcePropertyMetadata('foo');
        $targetMapperMetadata = new TargetPropertyMetadata('foo');
        $types = TypesMatching::fromSourceAndTargetTypes([new Type('object')], [new Type('object'), new Type('object')], );
        $transformer = $factory->getTransformer($types, $sourceMapperMetadata, $targetMapperMetadata, $mapperMetadata);

        self::assertNull($transformer);
    }
}
