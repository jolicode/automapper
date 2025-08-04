<?php

declare(strict_types=1);

namespace AutoMapper\Tests\Transformer;

use AutoMapper\Transformer\ObjectTransformer;
use PHPUnit\Framework\TestCase;
use Symfony\Component\PropertyInfo\Type;

class ObjectTransformerTest extends TestCase
{
    use EvalTransformerTrait;

    public function testObjectTransformer(): void
    {
        $transformer = new ObjectTransformer(new Type('object', false, Foo::class), new Type('object', false, Foo::class));

        $function = $this->createTransformerFunction($transformer);
        $class = new class {
            public $mappers;

            public function __construct()
            {
                $this->mappers['Mapper_' . Foo::class . '_' . Foo::class] = new class {
                    public function map()
                    {
                        return new Foo();
                    }
                };
            }
        };

        $transform = \Closure::bind($function, $class);
        $output = $transform(new Foo());

        self::assertNotNull($output);
        self::assertInstanceOf(Foo::class, $output);
    }
}

class Foo
{
    public string $bar;
}
