<?php

declare(strict_types=1);

namespace AutoMapper\Generator;

use AutoMapper\Exception\CircularReferenceException;
use AutoMapper\MapperContext;
use AutoMapper\Metadata\GeneratorMetadata;
use AutoMapper\Transformer\MapperDependency;
use PhpParser\Node\Arg;
use PhpParser\Node\ClosureUse;
use PhpParser\Node\Expr;
use PhpParser\Node\Name;
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
            // Guard before building the stream, so an invalid graph fails on map() rather than on
            // the first chunk pulled out of the generator.
            ...$this->handleCircularReference($metadata),
            $this->incrementDepth($metadata),
            new Stmt\Expression(new Expr\Assign($streamVar, new Expr\FuncCall($streamClosure))),
            new Stmt\Return_($streamVar),
        ];
    }

    /**
     * A JSON stream cannot reference a value it already wrote, so a circular reference can only be
     * reported. The detection itself reuses the regular mapping concepts, honouring the configured
     * `circular_reference_limit`.
     *
     * ```php
     * $sourceHash = spl_object_hash($value) . 'json';
     *
     * if (\AutoMapper\MapperContext::shouldHandleCircularReference($context, $sourceHash)) {
     *     throw new CircularReferenceException(...);
     * }
     *
     * $context = \AutoMapper\MapperContext::withReference($context, $sourceHash, $value);
     * ```
     *
     * @return Stmt[]
     */
    private function handleCircularReference(GeneratorMetadata $metadata): array
    {
        // The json dependencies are the nested object → json mappers this one delegates to, so an
        // empty list means the stream cannot recurse at all. `canHaveCircularReference()` cannot be
        // used here: it inspects the `array` dependencies, not the json ones.
        if ([] === $this->jsonDependencies($metadata)) {
            return [];
        }

        $variableRegistry = $metadata->variableRegistry;
        $hashVar = $variableRegistry->getHash();
        $contextVar = $variableRegistry->getContext();
        $sourceVar = $variableRegistry->getSourceInput();

        return [
            new Stmt\Expression(new Expr\Assign(
                $hashVar,
                new Expr\BinaryOp\Concat(
                    new Expr\FuncCall(new Name('spl_object_hash'), [new Arg($sourceVar)]),
                    new Scalar\String_($metadata->mapperMetadata->target),
                )
            )),
            new Stmt\If_(
                new Expr\StaticCall(new Name\FullyQualified(MapperContext::class), 'shouldHandleCircularReference', [
                    new Arg($contextVar),
                    new Arg($hashVar),
                ]),
                [
                    'stmts' => [
                        new Stmt\Expression(new Expr\Throw_(new Expr\New_(
                            new Name\FullyQualified(CircularReferenceException::class),
                            [new Arg(new Expr\BinaryOp\Concat(
                                new Scalar\String_('A circular reference has been detected while streaming the object of type "'),
                                new Expr\BinaryOp\Concat(
                                    new Expr\ClassConstFetch($sourceVar, 'class'),
                                    new Scalar\String_('" to JSON.'),
                                ),
                            ))]
                        ))),
                    ],
                ]
            ),
            new Stmt\Expression(new Expr\Assign(
                $contextVar,
                new Expr\StaticCall(new Name\FullyQualified(MapperContext::class), 'withReference', [
                    new Arg($contextVar),
                    new Arg($hashVar),
                    new Arg($sourceVar),
                ])
            )),
        ];
    }

    /**
     * ```php
     * $context = \AutoMapper\MapperContext::withIncrementedDepth($context);
     * ```.
     *
     * Keeps `MAX_DEPTH` and the `#[MaxDepth]` attribute working on the nested json mappers.
     */
    private function incrementDepth(GeneratorMetadata $metadata): Stmt
    {
        $contextVar = $metadata->variableRegistry->getContext();

        return new Stmt\Expression(new Expr\Assign(
            $contextVar,
            new Expr\StaticCall(new Name\FullyQualified(MapperContext::class), 'withIncrementedDepth', [
                new Arg($contextVar),
            ])
        ));
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
