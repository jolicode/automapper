<?php

declare(strict_types=1);

namespace AutoMapper\Tests\Transformer;

use AutoMapper\Transformer\BuiltinTransformer;
use AutoMapper\Transformer\MultipleTransformer;
use PHPUnit\Framework\TestCase;
use Symfony\Component\PropertyInfo\Type;

class MultipleTransformerTest extends TestCase
{
    use EvalTransformerTrait;

    public function testMultipleTransformer(): void
    {
        $transformer = new MultipleTransformer([
            [
                'transformer' => new BuiltinTransformer(new Type('string'), [new Type('int')]),
                'type' => new Type('string'),
            ],
            [
                'transformer' => new BuiltinTransformer(new Type('int'), [new Type('string')]),
                'type' => new Type('int'),
            ],
        ]);

        $output = $this->evalTransformer($transformer, '12');

        self::assertSame(12, $output);

        $output = $this->evalTransformer($transformer, 12);

        self::assertSame('12', $output);
    }
}
