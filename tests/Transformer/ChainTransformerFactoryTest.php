<?php

declare(strict_types=1);

namespace AutoMapper\Tests\Transformer;

use AutoMapper\MapperMetadata;
use AutoMapper\Transformer\ChainTransformerFactory;
use AutoMapper\Transformer\CopyTransformer;
use AutoMapper\Transformer\TransformerFactoryInterface;
use PHPUnit\Framework\TestCase;

class ChainTransformerFactoryTest extends TestCase
{
    public function testGetTransformer(): void
    {
        $transformer = new CopyTransformer();
        $mapperMetadata = $this->getMockBuilder(MapperMetadata::class)->disableOriginalConstructor()->getMock();
        $subTransformer = $this
            ->getMockBuilder(TransformerFactoryInterface::class)
            ->getMock()
        ;

        $subTransformer->expects($this->any())->method('getTransformer')->willReturn($transformer);

        $chainTransformerFactory = new ChainTransformerFactory([$subTransformer]);

        $transformerReturned = $chainTransformerFactory->getTransformer([], [], $mapperMetadata);

        self::assertSame($transformer, $transformerReturned);
    }

    public function testNoTransformer(): void
    {
        $mapperMetadata = $this->getMockBuilder(MapperMetadata::class)->disableOriginalConstructor()->getMock();
        $subTransformer = $this
            ->getMockBuilder(TransformerFactoryInterface::class)
            ->getMock()
        ;

        $subTransformer->expects($this->any())->method('getTransformer')->willReturn(null);
        $chainTransformerFactory = new ChainTransformerFactory([$subTransformer]);

        $transformerReturned = $chainTransformerFactory->getTransformer([], [], $mapperMetadata);

        self::assertNull($transformerReturned);
    }
}
