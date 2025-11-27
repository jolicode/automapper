<?php

declare(strict_types=1);

namespace AutoMapper\Tests\Transformer;

use AutoMapper\Transformer\BuiltinTransformer;
use AutoMapper\Transformer\NullableTransformer;
use PHPUnit\Framework\TestCase;
use Symfony\Component\TypeInfo\Type;

class NullableTransformerTest extends TestCase
{
    use EvalTransformerTrait;

    public function testNullTransformerTargetNullable(): void
    {
        $transformer = new NullableTransformer(new BuiltinTransformer(Type::string(), Type::nullable(Type::string())), true);

        $output = $this->evalTransformer($transformer, 'foo');

        self::assertSame('foo', $output);

        $output = $this->evalTransformer($transformer, null);

        self::assertNull($output);
    }

    public function testNullTransformerTargetNotNullable(): void
    {
        $transformer = new NullableTransformer(new BuiltinTransformer(Type::string(), Type::string()), false);

        $output = $this->evalTransformer($transformer, 'foo');

        self::assertSame('foo', $output);
    }
}
