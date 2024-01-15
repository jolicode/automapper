<?php

declare(strict_types=1);

namespace AutoMapper\Tests\Extractor;

use AutoMapper\Exception\InvalidArgumentException;
use AutoMapper\Exception\RuntimeException;
use AutoMapper\Extractor\ClassMethodToCallbackExtractor;
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
class ClassMethodToCallbackExtractorTest extends TestCase
{
    /**
     * @dataProvider extractSimpleMethodProvider
     */
    public function testExtractSimpleMethod(string $varName): void
    {
        $extractor = new ClassMethodToCallbackExtractor();
        $extractedMethod = new Expression($extractor->extract(FooCustomMapper::class, 'transform', [new Arg(new Variable($varName))]));

        // used for compatibility with older versions of nikic/php-parser
        $generatedCode = (new Standard())->prettyPrint([$extractedMethod]);
        $generatedCode = str_replace(') : mixed', '): mixed', $generatedCode);

        $this->assertEquals(<<<PHP
(function (mixed \$object): mixed {
    if (\$object instanceof \AutoMapper\Tests\Extractor\Fixtures\Foo) {
        \$object->bar = 'Hello World!';
    }
    return \$object;
})(\${$varName});
PHP, $generatedCode);

        $this->assertGeneratedCodeIsRunnable($generatedCode, $varName);
    }

    public function extractSimpleMethodProvider(): iterable
    {
        yield 'with same variable names' => ['object'];
        yield 'with different variable names' => ['someVar'];
    }

    public function testExtractMethodWithTwoVariables(): void
    {
        $extractor = new ClassMethodToCallbackExtractor();
        $extractedMethod = new Expression($extractor->extract(FooCustomMapper::class, 'switch', [new Arg(new Variable('someVar')), new Arg(new Variable('context'))]));

        // used for compatibility with older versions of nikic/php-parser
        $generatedCode = (new Standard())->prettyPrint([$extractedMethod]);
        $generatedCode = str_replace(') : mixed', '): mixed', $generatedCode);

        $this->assertEquals(<<<PHP
(function (mixed \$object, string \$someString): mixed {
    if (\$object instanceof \AutoMapper\Tests\Extractor\Fixtures\Foo) {
        \$object->bar = 'Hello World!';
        \$object->baz = \$someString;
    }
    return \$object;
})(\$someVar, \$context);
PHP, $generatedCode);
    }

    public function testCannotExtractCode(): void
    {
        $coreClass = \Generator::class;

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage("You cannot extract code from \"{$coreClass}\" class.");

        $extractor = new ClassMethodToCallbackExtractor();
        $extractor->extract($coreClass, 'rewind', [new Arg(new Variable('object'))]);
    }

    public function testInvalidInputParameters(): void
    {
        $class = FooCustomMapper::class;

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Input parameters and method parameters in class \"{$class}\" do not match.");

        $extractor = new ClassMethodToCallbackExtractor();
        $extractor->extract($class, 'transform', [new Arg(new Variable('object')), new Arg(new Variable('context'))]);
    }

    public function testInvalidExtractedMethodParameters(): void
    {
        $class = FooCustomMapper::class;

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Input parameters and method parameters in class \"{$class}\" do not match.");

        $extractor = new ClassMethodToCallbackExtractor();
        $extractor->extract($class, 'switch', [new Arg(new Variable('object'))]);
    }

    private function assertGeneratedCodeIsRunnable(string $generatedCode, string $varName): void
    {
        $codeToEval = <<<PHP
if(!class_exists(\AutoMapper\Tests\Extractor\Fixtures\Foo::class))
{
    class Foo
    {
        public string \$bar;
        public string \$baz;
    }
}

\${$varName} = new \AutoMapper\Tests\Extractor\Fixtures\Foo();
\${$varName}->bar = 'Hello';

{$generatedCode}

return \${$varName};
PHP;

        /** @var Foo $object */
        $object = eval($codeToEval);
        $this->assertEquals('Hello World!', $object->bar);
    }
}
