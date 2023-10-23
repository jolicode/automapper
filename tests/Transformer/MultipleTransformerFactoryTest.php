<?php

declare(strict_types=1);

namespace AutoMapper\Tests\Transformer;

use AutoMapper\MapperMetadata;
use AutoMapper\Transformer\BuiltinTransformer;
use AutoMapper\Transformer\BuiltinTransformerFactory;
use AutoMapper\Transformer\ChainTransformerFactory;
use AutoMapper\Transformer\MultipleTransformer;
use AutoMapper\Transformer\MultipleTransformerFactory;
use PHPUnit\Framework\TestCase;
use Symfony\Component\PropertyInfo\Type;

class MultipleTransformerFactoryTest extends TestCase
{
    public function testGetTransformer(): void
    {
        $chainFactory = new ChainTransformerFactory();
        $factory = new MultipleTransformerFactory($chainFactory);

        $chainFactory->addTransformerFactory($factory);
        $chainFactory->addTransformerFactory(new BuiltinTransformerFactory());

        $mapperMetadata = $this->getMockBuilder(MapperMetadata::class)->disableOriginalConstructor()->getMock();

        $transformer = $factory->getTransformer([new Type('string'), new Type('int')], [], $mapperMetadata);

        self::assertNotNull($transformer);
        self::assertInstanceOf(MultipleTransformer::class, $transformer);

        $transformer = $factory->getTransformer([new Type('string'), new Type('object')], [], $mapperMetadata);

        self::assertNotNull($transformer);
        self::assertInstanceOf(BuiltinTransformer::class, $transformer);
    }

    public function testNoTransformerIfNoSubTransformer(): void
    {
        $chainFactory = new ChainTransformerFactory();
        $factory = new MultipleTransformerFactory($chainFactory);

        $mapperMetadata = $this->getMockBuilder(MapperMetadata::class)->disableOriginalConstructor()->getMock();

        $transformer = $factory->getTransformer([new Type('string'), new Type('int')], [], $mapperMetadata);

        self::assertNull($transformer);
    }

    public function testNoTransformer(): void
    {
        $chainFactory = new ChainTransformerFactory();
        $factory = new MultipleTransformerFactory($chainFactory);

        $chainFactory->addTransformerFactory($factory);
        $chainFactory->addTransformerFactory(new BuiltinTransformerFactory());

        $mapperMetadata = $this->getMockBuilder(MapperMetadata::class)->disableOriginalConstructor()->getMock();

        $transformer = $factory->getTransformer(null, null, $mapperMetadata);

        self::assertNull($transformer);

        $transformer = $factory->getTransformer([], null, $mapperMetadata);

        self::assertNull($transformer);

        $transformer = $factory->getTransformer([new Type('string')], null, $mapperMetadata);

        self::assertNull($transformer);
    }
}
