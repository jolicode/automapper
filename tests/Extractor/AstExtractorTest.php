<?php

declare(strict_types=1);

namespace AutoMapper\Tests\Extractor;

use AutoMapper\Exception\InvalidArgumentException;
use AutoMapper\Exception\RuntimeException;
use AutoMapper\Extractor\AstExtractor;
use AutoMapper\Tests\Extractor\Fixtures\Foo;
use AutoMapper\Tests\Extractor\Fixtures\FooCustomMapper;
use PhpParser\Node\Arg;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Stmt\Expression;
use PhpParser\PrettyPrinter\Standard;
use PHPUnit\Framework\TestCase;

/**
 * @author Baptiste Leduc <baptiste.leduc@gmail.com>
 */
class AstExtractorTest extends TestCase
{
    public function testExtractSimpleMethod(): void
    {
        $extractor = new AstExtractor();
        $extractedMethod = new Expression($extractor->extract(FooCustomMapper::class, 'transform', [new Arg(new Variable('object'))]));

        $this->assertEquals(<<<PHP
(function (mixed \$object) : mixed {
    if (\$object instanceof Foo) {
        \$object->bar = 'Hello World!';
    }
    return \$object;
})(\$object);
PHP, $generatedCode = (new Standard())->prettyPrint([$extractedMethod]));

        $codeToEval = <<<PHP
class Foo
{
    public string \$bar;
    public string \$baz;
}

\$object = new Foo();
\$object->bar = 'Hello';

{$generatedCode}

return \$object;
PHP;

        /** @var Foo $object */
        $object = eval($codeToEval);
        $this->assertEquals('Hello World!', $object->bar);
    }

    public function testCannotExtractCode(): void
    {
        $coreClass = \Generator::class;

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage("You cannot extract code from \"{$coreClass}\" class.");

        $extractor = new AstExtractor();
        $extractor->extract($coreClass, 'rewind', [new Arg(new Variable('object'))]);
    }

    public function testInvalidInputParameters(): void
    {
        $class = FooCustomMapper::class;

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Input parameters and method parameters in class \"{$class}\" do not match.");

        $extractor = new AstExtractor();
        $extractor->extract($class, 'transform', [new Arg(new Variable('object')), new Arg(new Variable('context'))]);
    }

    public function testInvalidExtractedMethodParameters(): void
    {
        $class = FooCustomMapper::class;

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Input parameters and method parameters in class \"{$class}\" do not match.");

        $extractor = new AstExtractor();
        $extractor->extract($class, 'switch', [new Arg(new Variable('object'))]);
    }
}
