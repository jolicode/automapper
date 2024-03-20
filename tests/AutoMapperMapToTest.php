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
        $this->assertSame('foo', $bar->from);
        $this->assertSame('d', $bar->getB());
    }

    public function testMapToArray()
    {
        $this->buildAutoMapper(propertyTransformers: [new TransformerWithDependency(new FooDependency())]);

        $foo = new FooMapTo('foo');
        $bar = $this->autoMapper->map($foo, 'array');

        $this->assertIsArray($bar);
        $this->assertArrayNotHasKey('bar', $bar);
        $this->assertArrayNotHasKey('a', $bar);
        $this->assertSame('foo', $bar['baz']);
        $this->assertSame('foo', $bar['foo']);
        $this->assertSame('transformFromIsCallable_foo', $bar['transformFromIsCallable']);
        $this->assertSame('transformFromStringInstance_foo', $bar['transformFromStringInstance']);
        $this->assertSame('transformFromStringStatic_foo', $bar['transformFromStringStatic']);
        $this->assertSame('bar', $bar['transformFromCustomTransformerService']);
        $this->assertSame('if', $bar['if']);
        $this->assertSame('if', $bar['ifCallableStatic']);
        $this->assertSame('if', $bar['ifCallable']);
        $this->assertSame('if', $bar['ifCallableOther']);

        $foo = new FooMapTo('bar');
        $this->autoMapper->bindCustomTransformer(new TransformerWithDependency(new FooDependency()));
        $bar = $this->autoMapper->map($foo, 'array');

        $this->assertIsArray($bar);
        $this->assertArrayNotHasKey('bar', $bar);
        $this->assertArrayNotHasKey('a', $bar);
        $this->assertArrayNotHasKey('if', $bar);
        $this->assertArrayNotHasKey('ifCallableStatic', $bar);
        $this->assertArrayNotHasKey('ifCallable', $bar);
        $this->assertSame('if', $bar['ifCallableOther']);
        $this->assertSame('bar', $bar['baz']);
        $this->assertSame('bar', $bar['foo']);
        $this->assertSame('transformFromIsCallable_bar', $bar['transformFromIsCallable']);
        $this->assertSame('transformFromStringInstance_bar', $bar['transformFromStringInstance']);
        $this->assertSame('transformFromStringStatic_bar', $bar['transformFromStringStatic']);
        $this->assertSame('bar', $bar['transformFromCustomTransformerService']);
    }

    public function testMapFromArray()
    {
        $foo = ['b' => 'foo', 'bar' => 'bar', 'baz' => 'baz', 'foo' => 'foo'];
        $bar = $this->autoMapper->map($foo, Bar::class);

        $this->assertSame('bar', $bar->bar);
        $this->assertSame('baz', $bar->baz);
        $this->assertSame('foo', $bar->from);
        $this->assertSame('', $bar->getB());
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
