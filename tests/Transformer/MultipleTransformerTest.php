<?php

declare(strict_types=1);

namespace AutoMapper\Tests\Transformer;

use AutoMapper\Transformer\BuiltinTransformer;
use AutoMapper\Transformer\MultipleTransformer;
use PHPUnit\Framework\TestCase;
use Symfony\Component\TypeInfo\Type;

class MultipleTransformerTest extends TestCase
{
    use EvalTransformerTrait;

    public function testMultipleTransformer(): void
    {
        $transformer = new MultipleTransformer([
            [
                'transformer' => new BuiltinTransformer(Type::string(), Type::int()),
                'type' => Type::string(),
            ],
            [
                'transformer' => new BuiltinTransformer(Type::int(), Type::string()),
                'type' => Type::int(),
            ],
        ]);

        $output = $this->evalTransformer($transformer, '12');

        self::assertSame(12, $output);

        $output = $this->evalTransformer($transformer, 12);

        self::assertSame('12', $output);
    }
}
