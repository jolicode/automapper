<?php

declare(strict_types=1);

namespace AutoMapper\Extractor;

use AutoMapper\Exception\InvalidArgumentException;
use AutoMapper\Exception\RuntimeException;
use PhpParser\Node;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor\NameResolver;
use PhpParser\Parser;
use PhpParser\ParserFactory;

final readonly class AstExtractor
{
    private Parser $parser;

    public function __construct(?Parser $parser = null)
    {
        $this->parser = $parser ?? (new ParserFactory())->create(ParserFactory::PREFER_PHP7);
    }

    public function extractClassLike(string $class, bool $resolveImports = false): Node\Stmt\ClassLike
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

        if ($resolveImports) {
            $statements = $this->resolveFullyQualifiedClassNames($statements);
        }

        try {
            $namespaceStatement = self::findUnique(Node\Stmt\Namespace_::class, $statements, $fileName);
        } catch (\Exception) {
            // if no namespace, directly search for a class
            return self::findUnique(Node\Stmt\ClassLike::class, $statements, $fileName);
        }

        return self::findUnique(Node\Stmt\ClassLike::class, $namespaceStatement->stmts, $fileName);
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
