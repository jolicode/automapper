<?php

declare(strict_types=1);

namespace AutoMapper\Tests\Transformer;

use AutoMapper\MapperMetadata;
use AutoMapper\Transformer\BuiltinTransformer;
use AutoMapper\Transformer\BuiltinTransformerFactory;
use AutoMapper\Transformer\ChainTransformerFactory;
use AutoMapper\Transformer\UniqueTypeTransformerFactory;
use PHPUnit\Framework\TestCase;
use Symfony\Component\PropertyInfo\Type;

class UniqueTypeTransformerFactoryTest extends TestCase
{
    public function testGetTransformer(): void
    {
        $chainFactory = new ChainTransformerFactory([new BuiltinTransformerFactory()]);
        $factory = new UniqueTypeTransformerFactory();
        $factory->setChainTransformerFactory($chainFactory);

        $mapperMetadata = $this->getMockBuilder(MapperMetadata::class)->disableOriginalConstructor()->getMock();

        $transformer = $factory->getTransformer([new Type('string')], [new Type('string'), new Type('string')], $mapperMetadata);

        self::assertNotNull($transformer);
        self::assertInstanceOf(BuiltinTransformer::class, $transformer);
    }

    public function testNullTransformer(): void
    {
        $chainFactory = new ChainTransformerFactory([new BuiltinTransformerFactory()]);
        $factory = new UniqueTypeTransformerFactory();
        $factory->setChainTransformerFactory($chainFactory);

        $mapperMetadata = $this->getMockBuilder(MapperMetadata::class)->disableOriginalConstructor()->getMock();

        $transformer = $factory->getTransformer(null, [], $mapperMetadata);

        self::assertNull($transformer);

        $transformer = $factory->getTransformer([], [], $mapperMetadata);

        self::assertNull($transformer);

        $transformer = $factory->getTransformer([new Type('string')], [], $mapperMetadata);

        self::assertNull($transformer);

        $transformer = $factory->getTransformer([new Type('string'), new Type('string')], [], $mapperMetadata);

        self::assertNull($transformer);

        $transformer = $factory->getTransformer([new Type('string')], [new Type('string')], $mapperMetadata);

        self::assertNull($transformer);
    }
}
