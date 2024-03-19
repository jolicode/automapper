<?php

declare(strict_types=1);

namespace AutoMapper\Tests\Transformer;

use AutoMapper\Metadata\MapperMetadata;
use AutoMapper\Metadata\SourcePropertyMetadata;
use AutoMapper\Metadata\TargetPropertyMetadata;
use AutoMapper\Metadata\TypesMatching;
use AutoMapper\Transformer\DateTimeInterfaceToImmutableTransformer;
use AutoMapper\Transformer\DateTimeInterfaceToMutableTransformer;
use AutoMapper\Transformer\DateTimeToStringTransformer;
use AutoMapper\Transformer\DateTimeTransformerFactory;
use AutoMapper\Transformer\StringToDateTimeTransformer;
use PHPUnit\Framework\TestCase;
use Symfony\Component\PropertyInfo\Type;

class DateTimeTransformerFactoryTest extends TestCase
{
    public function testGetTransformer(): void
    {
        $factory = new DateTimeTransformerFactory();
        $mapperMetadata = $this->getMockBuilder(MapperMetadata::class)->disableOriginalConstructor()->getMock();

        $sourceMapperMetadata = new SourcePropertyMetadata('foo');
        $targetMapperMetadata = new TargetPropertyMetadata('foo');
        $types = TypesMatching::fromSourceAndTargetTypes([new Type('object', false, \DateTime::class)], [new Type('object', false, \DateTime::class)], );
        $transformer = $factory->getTransformer($types, $sourceMapperMetadata, $targetMapperMetadata, $mapperMetadata);

        self::assertNotNull($transformer);
        self::assertInstanceOf(DateTimeInterfaceToMutableTransformer::class, $transformer);

        $sourceMapperMetadata = new SourcePropertyMetadata('foo');
        $targetMapperMetadata = new TargetPropertyMetadata('foo');
        $types = TypesMatching::fromSourceAndTargetTypes([new Type('object', false, \DateTime::class)], [new Type('string')], );
        $transformer = $factory->getTransformer($types, $sourceMapperMetadata, $targetMapperMetadata, $mapperMetadata);

        self::assertNotNull($transformer);
        self::assertInstanceOf(DateTimeToStringTransformer::class, $transformer);

        $sourceMapperMetadata = new SourcePropertyMetadata('foo');
        $targetMapperMetadata = new TargetPropertyMetadata('foo');
        $types = TypesMatching::fromSourceAndTargetTypes([new Type('string')], [new Type('object', false, \DateTime::class)], );
        $transformer = $factory->getTransformer($types, $sourceMapperMetadata, $targetMapperMetadata, $mapperMetadata);

        self::assertNotNull($transformer);
        self::assertInstanceOf(StringToDateTimeTransformer::class, $transformer);
    }

    public function testGetTransformerImmutable(): void
    {
        $factory = new DateTimeTransformerFactory();
        $mapperMetadata = $this->getMockBuilder(MapperMetadata::class)->disableOriginalConstructor()->getMock();

        $sourceMapperMetadata = new SourcePropertyMetadata('foo');
        $targetMapperMetadata = new TargetPropertyMetadata('foo');
        $types = TypesMatching::fromSourceAndTargetTypes([new Type('object', false, \DateTimeImmutable::class)], [new Type('object', false, \DateTime::class)], );
        $transformer = $factory->getTransformer($types, $sourceMapperMetadata, $targetMapperMetadata, $mapperMetadata);

        self::assertNotNull($transformer);
        self::assertInstanceOf(DateTimeInterfaceToMutableTransformer::class, $transformer);
    }

    public function testGetTransformerMutable(): void
    {
        $factory = new DateTimeTransformerFactory();
        $mapperMetadata = $this->getMockBuilder(MapperMetadata::class)->disableOriginalConstructor()->getMock();

        $sourceMapperMetadata = new SourcePropertyMetadata('foo');
        $targetMapperMetadata = new TargetPropertyMetadata('foo');
        $types = TypesMatching::fromSourceAndTargetTypes([new Type('object', false, \DateTime::class)], [new Type('object', false, \DateTimeImmutable::class)], );
        $transformer = $factory->getTransformer($types, $sourceMapperMetadata, $targetMapperMetadata, $mapperMetadata);

        self::assertNotNull($transformer);
        self::assertInstanceOf(DateTimeInterfaceToImmutableTransformer::class, $transformer);
    }

    public function testNoTransformer(): void
    {
        $factory = new DateTimeTransformerFactory();
        $mapperMetadata = $this->getMockBuilder(MapperMetadata::class)->disableOriginalConstructor()->getMock();

        $sourceMapperMetadata = new SourcePropertyMetadata('foo');
        $targetMapperMetadata = new TargetPropertyMetadata('foo');
        $types = TypesMatching::fromSourceAndTargetTypes([new Type('string')], [new Type('string')], );
        $transformer = $factory->getTransformer($types, $sourceMapperMetadata, $targetMapperMetadata, $mapperMetadata);

        self::assertNull($transformer);

        $sourceMapperMetadata = new SourcePropertyMetadata('foo');
        $targetMapperMetadata = new TargetPropertyMetadata('foo');
        $types = TypesMatching::fromSourceAndTargetTypes([new Type('object', false, \DateTime::class)], [new Type('bool')], );
        $transformer = $factory->getTransformer($types, $sourceMapperMetadata, $targetMapperMetadata, $mapperMetadata);

        self::assertNull($transformer);

        $sourceMapperMetadata = new SourcePropertyMetadata('foo');
        $targetMapperMetadata = new TargetPropertyMetadata('foo');
        $types = TypesMatching::fromSourceAndTargetTypes([new Type('bool')], [new Type('object', false, \DateTime::class)], );
        $transformer = $factory->getTransformer($types, $sourceMapperMetadata, $targetMapperMetadata, $mapperMetadata);

        self::assertNull($transformer);
    }
}
