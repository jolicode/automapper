<?php

declare(strict_types=1);

namespace AutoMapper\Tests\Transformer\ApiPlatform;

use ApiPlatform\JsonLd\ContextBuilderInterface;
use ApiPlatform\Metadata\ResourceClassResolverInterface;
use AutoMapper\Transformer\ApiPlatform\JsonLdContextTransformer;
use PHPUnit\Framework\TestCase;

class JsonLdContextTransformerTest extends TestCase
{
    protected function setUp(): void
    {
        if (!interface_exists(ContextBuilderInterface::class)) {
            self::markTestSkipped('API Platform is not installed.');
        }
    }

    public function testComputedResourceClassTakesPrecedence(): void
    {
        $contextBuilder = $this->createMock(ContextBuilderInterface::class);
        $resourceClassResolver = $this->createMock(ResourceClassResolverInterface::class);

        // the source is NOT a resource: getResourceClass must never be called when a computed class is given
        $resourceClassResolver->expects(self::never())->method('isResourceClass');
        $resourceClassResolver->expects(self::never())->method('getResourceClass');

        $contextBuilder->expects(self::once())
            ->method('getResourceContextUri')
            ->with('App\\Entity\\Book')
            ->willReturn('/contexts/Book');

        $transformer = new JsonLdContextTransformer($contextBuilder, $resourceClassResolver);

        $result = $transformer->transform(null, new \stdClass(), [], 'App\\Entity\\Book');

        self::assertSame('/contexts/Book', $result);
    }

    public function testResolvesResourceClassWhenNotComputed(): void
    {
        $contextBuilder = $this->createMock(ContextBuilderInterface::class);
        $resourceClassResolver = $this->createMock(ResourceClassResolverInterface::class);

        $source = new \stdClass();

        $resourceClassResolver->method('isResourceClass')->with(\stdClass::class)->willReturn(true);
        $resourceClassResolver->method('getResourceClass')->with($source)->willReturn('App\\Entity\\Book');

        $contextBuilder->expects(self::once())
            ->method('getResourceContextUri')
            ->with('App\\Entity\\Book')
            ->willReturn('/contexts/Book');

        $transformer = new JsonLdContextTransformer($contextBuilder, $resourceClassResolver);

        self::assertSame('/contexts/Book', $transformer->transform(null, $source, []));
    }
}
