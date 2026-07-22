<?php

declare(strict_types=1);

namespace AutoMapper\Generator;

use AutoMapper\Metadata\GeneratorMetadata;
use AutoMapper\Transformer\MapperDependency;
use PhpParser\Node\Arg;
use PhpParser\Node\ClosureUse;
use PhpParser\Node\Expr;
use PhpParser\Node\Scalar;
use PhpParser\Node\Stmt;

/**
 * Generates the body of a `json` mapper's `map()` method: it yields the source object as a JSON
 * stream (`{` + each property + `}`), reusing {@see JsonPropertyStatementsGenerator} for every
 * property. This is the `json` counterpart of {@see MapMethodStatementsGenerator}.
 *
 * The body is wrapped in a closure so `map()` itself is not a generator and can return the stream
 * (by reference, like every other mapper).
 *
 * @internal
 */
final readonly class JsonMapMethodStatementsGenerator
{
    public function __construct(
        private JsonPropertyStatementsGenerator $jsonPropertyStatementsGenerator,
    ) {
    }

    /**
     * @return Stmt[]
     */
    public function getStatements(GeneratorMetadata $metadata): array
    {
        $variableRegistry = $metadata->variableRegistry;
        $separatorVar = JsonPropertyStatementsGenerator::separatorVariable();

        $bodyStatements = [
            // A scratch array so transformers referencing the target's existing value keep working.
            new Stmt\Expression(new Expr\Assign($variableRegistry->getResult(), new Expr\Array_())),
            new Stmt\Expression(new Expr\Yield_(new Scalar\String_('{'))),
            new Stmt\Expression(new Expr\Assign($separatorVar, new Scalar\String_(''))),
        ];

        foreach ($metadata->propertiesMetadata as $propertyMetadata) {
            $bodyStatements = [...$bodyStatements, ...$this->jsonPropertyStatementsGenerator->generate($metadata, $propertyMetadata)];
        }

        $bodyStatements[] = new Stmt\Expression(new Expr\Yield_(new Scalar\String_('}')));

        /** @var class-string<ClosureUse> $closureUseClass */
        $closureUseClass = class_exists(ClosureUse::class) ? ClosureUse::class : Arg::class;

        $streamVar = new Expr\Variable('stream');
        $streamClosure = new Expr\Closure([
            'uses' => [
                new $closureUseClass($variableRegistry->getSourceInput()),
                new $closureUseClass($variableRegistry->getContext()),
            ],
            'stmts' => $bodyStatements,
        ]);

        return [
            new Stmt\Expression(new Expr\Assign($streamVar, new Expr\FuncCall($streamClosure))),
            new Stmt\Return_($streamVar),
        ];
    }

    /**
     * The nested object → json dependencies referenced by this mapper, so they can be injected.
     *
     * @return MapperDependency[]
     */
    public function jsonDependencies(GeneratorMetadata $metadata): array
    {
        return $this->jsonPropertyStatementsGenerator->jsonDependencies($metadata);
    }
}
