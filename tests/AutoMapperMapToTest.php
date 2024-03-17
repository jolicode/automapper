<?php

declare(strict_types=1);

namespace AutoMapper\Tests;

use AutoMapper\Exception\BadMapDefinitionException;
use AutoMapper\Tests\Fixtures\MapTo\BadMapTo;
use AutoMapper\Tests\Fixtures\MapTo\BadMapToTransformer;
use AutoMapper\Tests\Fixtures\MapTo\Bar;
use AutoMapper\Tests\Fixtures\MapTo\FooMapTo;
use AutoMapper\Tests\Fixtures\Transformer\CustomTransformer\FooDependency;
use AutoMapper\Tests\Fixtures\Transformer\CustomTransformer\TransformerWithDependency;

/**
 * @author Joel Wurtz <jwurtz@jolicode.com>
 */
class AutoMapperMapToTest extends AutoMapperBaseTest
{
    public function testMapTo()
    {
        $foo = new FooMapTo('foo');
        $bar = $this->autoMapper->map($foo, Bar::class);

        $this->assertInstanceOf(Bar::class, $bar);
        $this->assertSame('foo', $bar->bar);
        $this->assertSame('foo', $bar->baz);
    }

    public function testMapToArray()
    {
        $foo = new FooMapTo('foo');
        $this->autoMapper->bindCustomTransformer(new TransformerWithDependency(new FooDependency()));
        $bar = $this->autoMapper->map($foo, 'array');

        $this->assertIsArray($bar);
        $this->assertArrayNotHasKey('bar', $bar);
        $this->assertSame('foo', $bar['baz']);
        $this->assertSame('foo', $bar['foo']);
        $this->assertSame('transformFromIsCallable_foo', $bar['transformFromIsCallable']);
        $this->assertSame('transformFromStringInstance_foo', $bar['transformFromStringInstance']);
        $this->assertSame('transformFromStringStatic_foo', $bar['transformFromStringStatic']);
        $this->assertSame('bar', $bar['transformFromCustomTransformerService']);
    }

    public function testBadDefinitionOnSameTargetProperty()
    {
        $foo = new BadMapTo('foo');

        $this->expectException(BadMapDefinitionException::class);
        $this->autoMapper->map($foo, 'array');
    }

    public function testBadDefinitionOnTransformer()
    {
        $foo = new BadMapToTransformer('foo');

        $this->expectException(BadMapDefinitionException::class);
        $this->autoMapper->map($foo, 'array');
    }
}
