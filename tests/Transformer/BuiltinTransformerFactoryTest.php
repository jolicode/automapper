<?php

declare(strict_types=1);

namespace AutoMapper\Tests\Transformer;

use AutoMapper\Metadata\MapperMetadata;
use AutoMapper\Metadata\SourcePropertyMetadata;
use AutoMapper\Metadata\TargetPropertyMetadata;
use AutoMapper\Transformer\BuiltinTransformer;
use AutoMapper\Transformer\BuiltinTransformerFactory;
use PHPUnit\Framework\TestCase;
use Symfony\Component\TypeInfo\Type;

class BuiltinTransformerFactoryTest extends TestCase
{
    public function testGetTransformer(): void
    {
        $factory = new BuiltinTransformerFactory();
        $mapperMetadata = $this->getMockBuilder(MapperMetadata::class)->disableOriginalConstructor()->getMock();

        $sourceMapperMetadata = new SourcePropertyMetadata('foo', type: Type::string());
        $targetMapperMetadata = new TargetPropertyMetadata('foo', type: Type::string());
        $transformer = $factory->getTransformer($sourceMapperMetadata, $targetMapperMetadata, $mapperMetadata);

        self::assertInstanceOf(BuiltinTransformer::class, $transformer);

        $sourceMapperMetadata = new SourcePropertyMetadata('foo', type: Type::bool());
        $targetMapperMetadata = new TargetPropertyMetadata('foo', type: Type::string());
        $transformer = $factory->getTransformer($sourceMapperMetadata, $targetMapperMetadata, $mapperMetadata);

        self::assertInstanceOf(BuiltinTransformer::class, $transformer);
    }

    public function testNoTransformer(): void
    {
        $factory = new BuiltinTransformerFactory();
        $mapperMetadata = $this->getMockBuilder(MapperMetadata::class)->disableOriginalConstructor()->getMock();

        $sourceMapperMetadata = new SourcePropertyMetadata('foo', type: Type::array());
        $targetMapperMetadata = new TargetPropertyMetadata('foo', type: Type::string());
        $transformer = $factory->getTransformer($sourceMapperMetadata, $targetMapperMetadata, $mapperMetadata);

        self::assertNull($transformer);

        $sourceMapperMetadata = new SourcePropertyMetadata('foo', type: Type::object(\stdClass::class));
        $targetMapperMetadata = new TargetPropertyMetadata('foo', type: Type::string());
        $transformer = $factory->getTransformer($sourceMapperMetadata, $targetMapperMetadata, $mapperMetadata);

        self::assertNull($transformer);
    }
}
