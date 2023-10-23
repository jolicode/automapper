<?php

declare(strict_types=1);

namespace AutoMapper\Tests\Transformer;

use AutoMapper\Transformer\BuiltinTransformer;
use PHPUnit\Framework\TestCase;
use Symfony\Component\PropertyInfo\Type;

class BuiltinTransformerTest extends TestCase
{
    use EvalTransformerTrait;

    public function testStringToString(): void
    {
        $transformer = new BuiltinTransformer(new Type('string'), [new Type('string')]);
        $output = $this->evalTransformer($transformer, 'foo');

        self::assertSame('foo', $output);
    }

    public function testStringToArray(): void
    {
        $transformer = new BuiltinTransformer(new Type('string'), [new Type('array')]);
        $output = $this->evalTransformer($transformer, 'foo');

        self::assertSame(['foo'], $output);
    }

    public function testStringToIterable(): void
    {
        $transformer = new BuiltinTransformer(new Type('string'), [new Type('iterable')]);
        $output = $this->evalTransformer($transformer, 'foo');

        self::assertSame(['foo'], $output);
    }

    public function testStringToFloat(): void
    {
        $transformer = new BuiltinTransformer(new Type('string'), [new Type('float')]);
        $output = $this->evalTransformer($transformer, '12.2');

        self::assertSame(12.2, $output);
    }

    public function testStringToInt(): void
    {
        $transformer = new BuiltinTransformer(new Type('string'), [new Type('int')]);
        $output = $this->evalTransformer($transformer, '12');

        self::assertSame(12, $output);
    }

    public function testStringToBool(): void
    {
        $transformer = new BuiltinTransformer(new Type('string'), [new Type('bool')]);
        $output = $this->evalTransformer($transformer, 'foo');

        self::assertTrue($output);

        $output = $this->evalTransformer($transformer, '');

        self::assertFalse($output);
    }

    public function testBoolToInt(): void
    {
        $transformer = new BuiltinTransformer(new Type('bool'), [new Type('int')]);
        $output = $this->evalTransformer($transformer, true);

        self::assertSame(1, $output);

        $output = $this->evalTransformer($transformer, false);

        self::assertSame(0, $output);
    }

    public function testBoolToString(): void
    {
        $transformer = new BuiltinTransformer(new Type('bool'), [new Type('string')]);

        $output = $this->evalTransformer($transformer, true);

        self::assertSame('1', $output);

        $output = $this->evalTransformer($transformer, false);

        self::assertSame('', $output);
    }

    public function testBoolToFloat(): void
    {
        $transformer = new BuiltinTransformer(new Type('bool'), [new Type('float')]);

        $output = $this->evalTransformer($transformer, true);

        self::assertSame(1.0, $output);

        $output = $this->evalTransformer($transformer, false);

        self::assertSame(0.0, $output);
    }

    public function testBoolToArray(): void
    {
        $transformer = new BuiltinTransformer(new Type('bool'), [new Type('array')]);

        $output = $this->evalTransformer($transformer, true);

        self::assertSame([true], $output);

        $output = $this->evalTransformer($transformer, false);

        self::assertSame([false], $output);
    }

    public function testBoolToIterable(): void
    {
        $transformer = new BuiltinTransformer(new Type('bool'), [new Type('iterable')]);

        $output = $this->evalTransformer($transformer, true);

        self::assertSame([true], $output);

        $output = $this->evalTransformer($transformer, false);

        self::assertSame([false], $output);
    }

    public function testBoolToBool(): void
    {
        $transformer = new BuiltinTransformer(new Type('bool'), [new Type('bool')]);

        $output = $this->evalTransformer($transformer, true);

        self::assertTrue($output);

        $output = $this->evalTransformer($transformer, false);

        self::assertFalse($output);
    }

    public function testFloatToString(): void
    {
        $transformer = new BuiltinTransformer(new Type('float'), [new Type('string')]);

        $output = $this->evalTransformer($transformer, 12.23);

        self::assertSame('12.23', $output);
    }

    public function testFloatToInt(): void
    {
        $transformer = new BuiltinTransformer(new Type('float'), [new Type('int')]);

        $output = $this->evalTransformer($transformer, 12.23);

        self::assertSame(12, $output);
    }

    public function testFloatToBool(): void
    {
        $transformer = new BuiltinTransformer(new Type('float'), [new Type('bool')]);

        $output = $this->evalTransformer($transformer, 12.23);

        self::assertTrue($output);

        $output = $this->evalTransformer($transformer, 0.0);

        self::assertFalse($output);
    }

    public function testFloatToArray(): void
    {
        $transformer = new BuiltinTransformer(new Type('float'), [new Type('array')]);

        $output = $this->evalTransformer($transformer, 12.23);

        self::assertSame([12.23], $output);
    }

    public function testFloatToIterable(): void
    {
        $transformer = new BuiltinTransformer(new Type('float'), [new Type('iterable')]);

        $output = $this->evalTransformer($transformer, 12.23);

        self::assertSame([12.23], $output);
    }

    public function testFloatToFloat(): void
    {
        $transformer = new BuiltinTransformer(new Type('float'), [new Type('float')]);

        $output = $this->evalTransformer($transformer, 12.23);

        self::assertSame(12.23, $output);
    }

    public function testIntToInt(): void
    {
        $transformer = new BuiltinTransformer(new Type('int'), [new Type('int')]);

        $output = $this->evalTransformer($transformer, 12);

        self::assertSame(12, $output);
    }

    public function testIntToFloat(): void
    {
        $transformer = new BuiltinTransformer(new Type('int'), [new Type('float')]);

        $output = $this->evalTransformer($transformer, 12);

        self::assertSame(12.0, $output);
    }

    public function testIntToString(): void
    {
        $transformer = new BuiltinTransformer(new Type('int'), [new Type('string')]);

        $output = $this->evalTransformer($transformer, 12);

        self::assertSame('12', $output);
    }

    public function testIntToBool(): void
    {
        $transformer = new BuiltinTransformer(new Type('int'), [new Type('bool')]);

        $output = $this->evalTransformer($transformer, 12);

        self::assertTrue($output);

        $output = $this->evalTransformer($transformer, 0);

        self::assertFalse($output);
    }

    public function testIntToArray(): void
    {
        $transformer = new BuiltinTransformer(new Type('int'), [new Type('array')]);

        $output = $this->evalTransformer($transformer, 12);

        self::assertSame([12], $output);
    }

    public function testIntToIterable(): void
    {
        $transformer = new BuiltinTransformer(new Type('int'), [new Type('iterable')]);

        $output = $this->evalTransformer($transformer, 12);

        self::assertSame([12], $output);
    }

    public function testIterableToArray(): void
    {
        $transformer = new BuiltinTransformer(new Type('iterable'), [new Type('array')]);

        $closure = function () {
            yield 1;
            yield 2;
        };

        $output = $this->evalTransformer($transformer, $closure());

        self::assertSame([1, 2], $output);
    }

    public function testArrayToIterable(): void
    {
        $transformer = new BuiltinTransformer(new Type('array'), [new Type('iterable')]);
        $output = $this->evalTransformer($transformer, [1, 2]);

        self::assertSame([1, 2], $output);
    }

    public function testToUnknowCast(): void
    {
        $transformer = new BuiltinTransformer(new Type('callable'), [new Type('string')]);

        $output = $this->evalTransformer($transformer, function ($test) {
            return $test;
        });

        self::assertIsCallable($output);
    }
}
