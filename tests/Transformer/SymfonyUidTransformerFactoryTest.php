<?php

declare(strict_types=1);

namespace AutoMapper\Tests\Transformer;

use AutoMapper\Metadata\MapperMetadata;
use AutoMapper\Metadata\SourcePropertyMetadata;
use AutoMapper\Metadata\TargetPropertyMetadata;
use AutoMapper\Transformer\SymfonyUidCopyTransformer;
use AutoMapper\Transformer\SymfonyUidTransformerFactory;
use PHPUnit\Framework\TestCase;
use Symfony\Component\TypeInfo\Type;
use Symfony\Component\Uid\Ulid;

class SymfonyUidTransformerFactoryTest extends TestCase
{
    public function testNoTransformer(): void
    {
        $factory = new SymfonyUidTransformerFactory();
        $mapperMetadata = $this->getMockBuilder(MapperMetadata::class)->disableOriginalConstructor()->getMock();
        $sourceMapperMetadata = new SourcePropertyMetadata('foo', type: Type::object());
        $targetMapperMetadata = new TargetPropertyMetadata('foo', type: Type::object());

        $transformer = $factory->getTransformer($sourceMapperMetadata, $targetMapperMetadata, $mapperMetadata);

        self::assertNull($transformer);
    }

    public function testGetUlidCopyTransformer(): void
    {
        $factory = new SymfonyUidTransformerFactory();
        $mapperMetadata = $this->getMockBuilder(MapperMetadata::class)->disableOriginalConstructor()->getMock();
        $sourceMapperMetadata = new SourcePropertyMetadata('foo', type: Type::object(Ulid::class));
        $targetMapperMetadata = new TargetPropertyMetadata('foo', type: Type::object(Ulid::class));

        $transformer = $factory->getTransformer($sourceMapperMetadata, $targetMapperMetadata, $mapperMetadata);

        self::assertNotNull($transformer);
        self::assertInstanceOf(SymfonyUidCopyTransformer::class, $transformer);
    }
}
