<?php

declare(strict_types=1);

namespace AutoMapper\Tests;

use AutoMapper\Exception\BadMapDefinitionException;
use AutoMapper\MapperContext;
use AutoMapper\Symfony\ExpressionLanguageProvider;
use AutoMapper\Tests\Fixtures\MapTo\BadMapTo;
use AutoMapper\Tests\Fixtures\MapTo\BadMapToTransformer;
use AutoMapper\Tests\Fixtures\MapTo\Bar;
use AutoMapper\Tests\Fixtures\MapTo\FooMapTo;
use AutoMapper\Tests\Fixtures\Transformer\CustomTransformer\FooDependency;
use AutoMapper\Tests\Fixtures\Transformer\CustomTransformer\TransformerWithDependency;
use Symfony\Component\DependencyInjection\ServiceLocator;

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
        $expressionLanguageProvider = new ExpressionLanguageProvider(new ServiceLocator([
            'transformerWithDependency' => fn () => fn () => new TransformerWithDependency(new FooDependency()),
        ]));

        $this->buildAutoMapper(propertyTransformers: [new TransformerWithDependency(new FooDependency())], expressionLanguageProvider: $expressionLanguageProvider);

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
        $this->assertSame('transformed', $bar['transformFromExpressionLanguage']);
        $this->assertSame('bar', $bar['transformWithExpressionFunction']);

        $foo = new FooMapTo('bar');
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
        $this->assertSame('not transformed', $bar['transformFromExpressionLanguage']);
    }

    public function testMapToArrayGroups()
    {
        $expressionLanguageProvider = new ExpressionLanguageProvider(new ServiceLocator([
            'transformerWithDependency' => fn () => fn () => new TransformerWithDependency(new FooDependency()),
        ]));

        $this->buildAutoMapper(propertyTransformers: [new TransformerWithDependency(new FooDependency())], expressionLanguageProvider: $expressionLanguageProvider);

        $foo = new FooMapTo('foo');
        $bar = $this->autoMapper->map($foo, 'array');

        self::assertArrayNotHasKey('externalProperty', $bar);

        $bar = $this->autoMapper->map($foo, 'array', [MapperContext::GROUPS => ['group1']]);

        self::assertArrayHasKey('externalProperty', $bar);
        self::assertSame('external', $bar['externalProperty']);

        $bar = $this->autoMapper->map($foo, 'array', [MapperContext::GROUPS => ['group2']]);

        self::assertArrayNotHasKey('externalProperty', $bar);
    }

    public function testMapFromArray()
    {
        $foo = ['b' => 'foo', 'bar' => 'bar', 'baz' => 'baz', 'foo' => 'foo', 'c' => 'foo', 'd' => 'foo'];
        $bar = $this->autoMapper->map($foo, Bar::class);

        $this->assertSame('bar', $bar->bar);
        $this->assertSame('baz', $bar->baz);
        $this->assertSame('foo', $bar->from);
        $this->assertSame('transformC_foo', $bar->c);
        $this->assertSame('transformDStatic_foo', $bar->d);
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
