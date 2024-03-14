<?php

declare(strict_types=1);

namespace AutoMapper\Tests;

use AutoMapper\Tests\Fixtures\MapTo\Bar;
use AutoMapper\Tests\Fixtures\MapTo\FooMapTo;

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
        $bar = $this->autoMapper->map($foo, 'array');

        $this->assertIsArray($bar);
        $this->assertArrayNotHasKey('bar', $bar);
        $this->assertSame('foo', $bar['baz']);
        $this->assertSame('FOO', $bar['foo']);
    }
}
