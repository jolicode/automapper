<?php

declare(strict_types=1);

namespace AutoMapper\Tests\Transformer;

use AutoMapper\MapperMetadata;
use AutoMapper\Transformer\CopyTransformer;
use AutoMapper\Transformer\DateTimeImmutableToMutableTransformer;
use AutoMapper\Transformer\DateTimeMutableToImmutableTransformer;
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

        $transformer = $factory->getTransformer([new Type('object', false, \DateTime::class)], [new Type('object', false, \DateTime::class)], $mapperMetadata);

        self::assertNotNull($transformer);
        self::assertInstanceOf(CopyTransformer::class, $transformer);

        $transformer = $factory->getTransformer([new Type('object', false, \DateTime::class)], [new Type('string')], $mapperMetadata);

        self::assertNotNull($transformer);
        self::assertInstanceOf(DateTimeToStringTransformer::class, $transformer);

        $transformer = $factory->getTransformer([new Type('string')], [new Type('object', false, \DateTime::class)], $mapperMetadata);

        self::assertNotNull($transformer);
        self::assertInstanceOf(StringToDateTimeTransformer::class, $transformer);
    }

    public function testGetTransformerImmutable(): void
    {
        $factory = new DateTimeTransformerFactory();
        $mapperMetadata = $this->getMockBuilder(MapperMetadata::class)->disableOriginalConstructor()->getMock();

        $transformer = $factory->getTransformer([new Type('object', false, \DateTimeImmutable::class)], [new Type('object', false, \DateTime::class)], $mapperMetadata);

        self::assertNotNull($transformer);
        self::assertInstanceOf(DateTimeImmutableToMutableTransformer::class, $transformer);
    }

    public function testGetTransformerMutable(): void
    {
        $factory = new DateTimeTransformerFactory();
        $mapperMetadata = $this->getMockBuilder(MapperMetadata::class)->disableOriginalConstructor()->getMock();

        $transformer = $factory->getTransformer([new Type('object', false, \DateTime::class)], [new Type('object', false, \DateTimeImmutable::class)], $mapperMetadata);

        self::assertNotNull($transformer);
        self::assertInstanceOf(DateTimeMutableToImmutableTransformer::class, $transformer);
    }

    public function testNoTransformer(): void
    {
        $factory = new DateTimeTransformerFactory();
        $mapperMetadata = $this->getMockBuilder(MapperMetadata::class)->disableOriginalConstructor()->getMock();

        $transformer = $factory->getTransformer([new Type('string')], [new Type('string')], $mapperMetadata);

        self::assertNull($transformer);

        $transformer = $factory->getTransformer([new Type('object', false, \DateTime::class)], [new Type('bool')], $mapperMetadata);

        self::assertNull($transformer);

        $transformer = $factory->getTransformer([new Type('bool')], [new Type('object', false, \DateTime::class)], $mapperMetadata);

        self::assertNull($transformer);
    }
}
