<?php

declare(strict_types=1);

namespace AutoMapper\Extractor;

use AutoMapper\Exception\InvalidArgumentException;
use AutoMapper\Exception\LogicException;
use AutoMapper\Exception\RuntimeException;
use PhpParser\Node;
use PhpParser\Node\Arg;
use PhpParser\Node\Expr;
use PhpParser\Node\Param;
use PhpParser\Node\Stmt;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor\NameResolver;
use PhpParser\Parser;
use PhpParser\ParserFactory;

/**
 * Extracts the code of the given method from a given class and wraps it inside a closure, in order to inject it
 * in the generated mappers.
 *
 * @author Nicolas Philippe <nikophil@gmail.com>
 * @author Baptiste Leduc <baptiste.leduc@gmail.com>
 *
 * @internal
 */
final readonly class ClassMethodToCallbackExtractor
{
    private Parser $parser;

    public function __construct(?Parser $parser = null)
    {
        $this->parser = $parser ?? (new ParserFactory())->create(ParserFactory::PREFER_PHP7);
    }

    /**
     * @param class-string $class
     * @param Arg[]        $inputParameters
     */
    public function extract(string $class, string $method, array $inputParameters): Expr
    {
        $fileName = (new \ReflectionClass($class))->getFileName();
        if (false === $fileName) {
            throw new RuntimeException("You cannot extract code from \"{$class}\" class.");
        }
        $fileContents = file_get_contents($fileName);
        if (false === $fileContents) {
            throw new RuntimeException("File \"{$fileName}\" for \"{$class}\" couldn't be read.");
        }

        $statements = $this->parser->parse($fileContents);

        if (null === $statements) {
            throw new RuntimeException("Couldn't parse file \"{$fileName}\" for class \"{$class}\".");
        }

        $statements = $this->resolveFullyQualifiedClassNames($statements);

        $namespaceStatement = self::findUnique(Stmt\Namespace_::class, $statements, $fileName);
        /** @var Stmt\Class_ $classStatement */
        $classStatement = self::findUnique(Stmt\Class_::class, $namespaceStatement->stmts, $fileName);

        $classMethod = $classStatement->getMethod($method) ?? throw new LogicException("Cannot find method \"{$method}()\" in class \"{$class}\".");

        if (\count($inputParameters) !== \count($classMethod->getParams())) {
            throw new InvalidArgumentException("Input parameters and method parameters in class \"{$class}\" do not match.");
        }

        $closureParameters = [];
        foreach ($classMethod->getParams() as $parameter) {
            if ($parameter->var instanceof Expr\Variable) {
                $closureParameters[] = new Param(new Expr\Variable($parameter->var->name), type: $parameter->type);
            }
        }

        return new Expr\FuncCall(
            new Expr\Closure([
                'stmts' => $classMethod->stmts,
                'params' => $closureParameters,
                'returnType' => $classMethod->returnType,
            ]),
            $inputParameters,
        );
    }

    /**
     * @template T of Node
     *
     * @param class-string<T> $searchedStatementClass
     * @param Node[]          $statements
     *
     * @return T
     */
    private static function findUnique(string $searchedStatementClass, array $statements, string $fileName): Node
    {
        $foundStatements = array_filter(
            $statements,
            static fn (Node $statement): bool => $statement instanceof $searchedStatementClass,
        );

        if (\count($foundStatements) > 1) {
            throw new InvalidArgumentException("Multiple \"{$searchedStatementClass}\" found in file \"{$fileName}\".");
        }

        return array_values($foundStatements)[0] ?? throw new InvalidArgumentException("No \"{$searchedStatementClass}\" found in file \"{$fileName}\".");
    }

    /**
     * Transform all statements with imported class names, into FQCNs.
     *
     * @param Node[] $statements
     *
     * @return Node[]
     */
    private function resolveFullyQualifiedClassNames(array $statements): array
    {
        $nodeTraverser = new NodeTraverser();
        $nodeTraverser->addVisitor(new NameResolver());

        return $nodeTraverser->traverse($statements);
    }
}
