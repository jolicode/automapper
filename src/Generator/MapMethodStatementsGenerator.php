<?php

declare(strict_types=1);

namespace AutoMapper\Generator;

use AutoMapper\Exception\ReadOnlyTargetException;
use AutoMapper\Generator\Shared\CachedReflectionStatementsGenerator;
use AutoMapper\Generator\Shared\DiscriminatorStatementsGenerator;
use AutoMapper\MapperContext;
use AutoMapper\Metadata\GeneratorMetadata;
use AutoMapper\Provider\EarlyReturn;
use PhpParser\Comment;
use PhpParser\Node\Arg;
use PhpParser\Node\Expr;
use PhpParser\Node\Name;
use PhpParser\Node\Scalar;
use PhpParser\Node\Stmt;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;

/**
 * @internal
 */
final readonly class MapMethodStatementsGenerator
{
    private CreateTargetStatementsGenerator $createObjectStatementsGenerator;
    private PropertyStatementsGenerator $propertyStatementsGenerator;

    public function __construct(
        DiscriminatorStatementsGenerator $discriminatorStatementsGeneratorSource,
        DiscriminatorStatementsGenerator $discriminatorStatementsGeneratorTarget,
        CachedReflectionStatementsGenerator $cachedReflectionStatementsGenerator,
        ExpressionLanguage $expressionLanguage,
    ) {
        $this->createObjectStatementsGenerator = new CreateTargetStatementsGenerator(
            $discriminatorStatementsGeneratorSource,
            $discriminatorStatementsGeneratorTarget,
            $cachedReflectionStatementsGenerator,
        );
        $this->propertyStatementsGenerator = new PropertyStatementsGenerator($expressionLanguage);
    }

    /**
     * @return array{0: list<Stmt>, 1: list<Stmt>, 2: list<Stmt>}
     */
    public function getMappingStatements(GeneratorMetadata $metadata): array
    {
        // Statements to be executed to construct the object with the constructor
        $constructorStatements = $this->createObjectStatementsGenerator->getConstructStatements($metadata);
        // Statements to be executed if the target is populated
        $duplicatedStatements = [];
        // Statements to be executed after the constructor
        $setterStatements = [];

        foreach ($metadata->propertiesMetadata as $propertyMetadata) {
            /**
             * This is the main loop to map the properties from the source to the target, there is 3 main steps in order to generate this code :.
             *
             *  * Generate code on how to read the value from the source, which returns statements and an output expression
             *  * Generate code on how to transform the value, which use the output expression, add some statements and return a new output expression
             *  * Generate code on how to write this transformed value to the target, which use the output expression and add some statements
             *
             * As an example this could generate the following code :
             *
             *  * Extract value from a private property : $this->extractCallbacks['propertyName']($source)
             *  * Transform the value, which is an object in this example, with another mapper : $this->mappers['SOURCE_TO_TARGET_MAPPER']->map(..., $context);
             *  * Write the value to a private property : $this->hydrateCallbacks['propertyName']($target, ...)
             *
             * Since it use expression that may not create variable this would produce the following code
             *
             * ```php
             * $this->hydrateCallbacks['propertyName']($target, $this->mappers['SOURCE_TO_TARGET_MAPPER']->map($this->extractCallbacks['propertyName']($source), $context));
             * ```
             */
            $propStatements = $this->propertyStatementsGenerator->generate($metadata, $propertyMetadata);

            /*
             * Dispatch those statements into two categories:
             * - Statements that need to be executed before the constructor, if the property needs to be written in the constructor
             * - Statements that need to be executed after the constructor.
             */
            if (\in_array($propertyMetadata->target->property, $metadata->getPropertiesInConstructor(), true)) {
                $duplicatedStatements = [...$duplicatedStatements, ...$propStatements];
            } else {
                $setterStatements = [...$setterStatements, ...$propStatements];
            }
        }

        return [$constructorStatements, $duplicatedStatements, $setterStatements];
    }

    /**
     * @param list<Stmt> $duplicatedStatements
     *
     * @return list<Stmt>
     */
    public function getStatements(GeneratorMetadata $metadata, array $duplicatedStatements, bool $callDoConstruct): array
    {
        $variableRegistry = $metadata->variableRegistry;

        $statements = [$this->ifSourceIsNullReturnNull($metadata)];
        $statements = [...$statements, ...$this->handleCircularReference($metadata)];
        $statements = [...$statements, ...$this->initializeTargetToPopulate($metadata)];
        $statements = [...$statements, ...$this->initializeTargetFromProvider($metadata)];
        $statements[] = $this->createObjectStatementsGenerator->generate($metadata, $variableRegistry, $callDoConstruct);

        $addedDependenciesStatements = $this->handleDependencies($metadata);

        if (\count($duplicatedStatements) > 0 && \count($metadata->getPropertiesInConstructor())) {
            /*
             * Generate else statements when the result is already an object, which means it has already been created,
             * so we need to execute the statements that need to be executed before the constructor since the constructor has already been called
             *
             * ```php
             * if (null !== $result) {
             *     .. // create object statements
             * } else {
             *     // remap property from the constructor in case object already exists so we do not loose information
             *     $source->propertyName = $this->extractCallbacks['propertyName']($source);
             *     ...
             * }
             * ```
             */
            $statements[] = new Stmt\Else_(array_merge($addedDependenciesStatements, $duplicatedStatements));
        } else {
            $statements = [...$statements, ...$addedDependenciesStatements];
        }

        $mapStatement = new Stmt\Expression(new Expr\MethodCall(
            new Expr\Variable('this'),
            'doMap',
            [
                new Arg($variableRegistry->getSourceInput()),
                new Arg($variableRegistry->getResult()),
                new Arg($variableRegistry->getContext()),
            ]
        ));

        return [
            ...$statements,
            $mapStatement,
            new Stmt\Return_($variableRegistry->getResult()),
        ];
    }

    /**
     * If the source is null, if so, return null.
     *
     * ```php
     * if (null === $source) {
     *    return $source;
     * }
     * ```
     */
    private function ifSourceIsNullReturnNull(GeneratorMetadata $metadata): Stmt
    {
        return new Stmt\If_(
            new Expr\BinaryOp\Identical(new Expr\ConstFetch(new Name('null')), $metadata->variableRegistry->getSourceInput()),
            [
                'stmts' => [new Stmt\Return_($metadata->variableRegistry->getSourceInput())],
            ]
        );
    }

    /**
     * When there can be circular dependency in the mapping,
     * the following statements try to use the reference for the source if it's available.
     *
     * ```php
     * $sourceHash = spl_object_hash($source) . $target;
     * if (MapperContext::shouldHandleCircularReference($context, $sourceHash)) {
     *     return MapperContext::handleCircularReference($context, $sourceHash, $source);
     * }
     * ```
     *
     * @return list<Stmt>
     */
    private function handleCircularReference(GeneratorMetadata $metadata): array
    {
        if (!$metadata->canHaveCircularReference()) {
            return [];
        }

        $variableRegistry = $metadata->variableRegistry;

        return [
            new Stmt\Expression(
                new Expr\Assign(
                    $variableRegistry->getHash(),
                    new Expr\BinaryOp\Concat(new Expr\FuncCall(new Name('spl_object_hash'), [
                        new Arg($variableRegistry->getSourceInput()),
                    ]),
                        new Scalar\String_($metadata->mapperMetadata->target)
                    )
                )
            ),
            new Stmt\If_(
                new Expr\StaticCall(new Name\FullyQualified(MapperContext::class), 'shouldHandleCircularReference', [
                    new Arg($variableRegistry->getContext()),
                    new Arg($variableRegistry->getHash()),
                ]), [
                    'stmts' => [
                        new Stmt\Return_(
                            new Expr\StaticCall(
                                new Name\FullyQualified(MapperContext::class),
                                'handleCircularReference',
                                [
                                    new Arg($variableRegistry->getContext()),
                                    new Arg($variableRegistry->getHash()),
                                    new Arg($variableRegistry->getSourceInput()),
                                ]
                            )
                        ),
                    ],
                ]
            ),
        ];
    }

    /**
     * @return list<Stmt>
     */
    private function initializeTargetToPopulate(GeneratorMetadata $metadata): array
    {
        $variableRegistry = $metadata->variableRegistry;
        $targetToPopulate = new Expr\ArrayDimFetch($variableRegistry->getContext(), new Scalar\String_(MapperContext::TARGET_TO_POPULATE));

        $statements = [];

        /*
         * Get result from context if available, otherwise set it to null
         *
         * ```php
         * $result = $context[MapperContext::TARGET_TO_POPULATE] ?? null;
         * ```
         */
        $statements[] = new Stmt\Expression(
            new Expr\Assign(
                $variableRegistry->getResult(),
                new Expr\BinaryOp\Coalesce($targetToPopulate, new Expr\ConstFetch(new Name('null')))
            ),
            ['comments' => [new Comment(sprintf('/** @var %s $result */', $metadata->mapperMetadata->target === 'array' ? $metadata->mapperMetadata->target : '\\' . $metadata->mapperMetadata->target))]]
        );

        if (!$metadata->allowReadOnlyTargetToPopulate && $metadata->isTargetReadOnlyClass()) {
            /*
             * If the target is a read-only class, we throw an exception if the target is not null
             *
             * ```php
             * if ($contextVariable[MapperContext::ALLOW_READONLY_TARGET_TO_POPULATE] ?? false && is_object($targetToPopulate)) {
             *     throw new ReadOnlyTargetException();
             * }
             * ```
             */
            $statements[] = new Stmt\If_(
                new Expr\BinaryOp\BooleanAnd(
                    new Expr\BooleanNot(
                        new Expr\BinaryOp\Coalesce(
                            new Expr\ArrayDimFetch(
                                $variableRegistry->getContext(),
                                new Scalar\String_(MapperContext::ALLOW_READONLY_TARGET_TO_POPULATE)
                            ), new Expr\ConstFetch(new Name('false'))
                        )
                    ),
                    new Expr\FuncCall(
                        new Name('is_object'),
                        [new Arg(new Expr\BinaryOp\Coalesce($targetToPopulate, new Expr\ConstFetch(new Name('null'))))]
                    )
                ), [
                    'stmts' => [
                        new Stmt\Expression(
                            new Expr\Throw_(new Expr\New_(new Name(ReadOnlyTargetException::class)))
                        ),
                    ],
                ]
            );
        }

        return $statements;
    }

    /**
     * @return list<Stmt>
     */
    private function initializeTargetFromProvider(GeneratorMetadata $metadata): array
    {
        if ($metadata->provider === null) {
            return [];
        }

        $variableRegistry = $metadata->variableRegistry;

        /*
         * Get result from provider if available
         *
         * ```php
         * $result ??= $this->providerRegistry->getProvider($metadata->provider)->provide($source, $context);
         *
         * if ($result instanceof EarlyReturn) {
         *     return $result->value;
         * }
         * ```
         */
        $statements = [];
        $statements[] = new Stmt\Expression(
            new Expr\AssignOp\Coalesce(
                $variableRegistry->getResult(),
                new Expr\MethodCall(new Expr\MethodCall(new Expr\PropertyFetch(new Expr\Variable('this'), 'providerRegistry'), 'getProvider', [
                    new Arg(new Scalar\String_($metadata->provider)),
                ]), 'provide', [
                    new Arg(new Scalar\String_($metadata->mapperMetadata->target)),
                    new Arg($variableRegistry->getSourceInput()),
                    new Arg($variableRegistry->getContext()),
                ]),
            )
        );

        $statements[] = new Stmt\If_(
            new Expr\Instanceof_($variableRegistry->getResult(), new Name(EarlyReturn::class)),
            [
                'stmts' => [
                    new Stmt\Return_(
                        new Expr\PropertyFetch($variableRegistry->getResult(), 'value')
                    ),
                ],
            ]
        );

        return $statements;
    }

    /**
     * @return list<Stmt>
     */
    private function handleDependencies(GeneratorMetadata $metadata): array
    {
        if (!$metadata->getDependencies()) {
            return [
                new Stmt\Expression(
                    new Expr\Assign(
                        $metadata->variableRegistry->getContext(),
                        new Expr\StaticCall(new Name\FullyQualified(MapperContext::class), 'withIncrementedDepth', [
                            new Arg($metadata->variableRegistry->getContext()),
                        ])
                    )
                ),
            ];
        }

        $variableRegistry = $metadata->variableRegistry;

        $addedDependenciesStatements = [];
        if ($metadata->canHaveCircularReference()) {
            /*
             * Here we register the result into the context to allow circular dependency, it's done before mapping so if there is a circular dependency, it will be correctly handled
             *
             * ```php
             * $context = MapperContext::withReference($context, $sourceHash, $result);
             * ```
             */
            $addedDependenciesStatements[] = new Stmt\Expression(
                new Expr\Assign(
                    $variableRegistry->getContext(),
                    new Expr\StaticCall(new Name\FullyQualified(MapperContext::class), 'withReference', [
                        new Arg($variableRegistry->getContext()),
                        new Arg($variableRegistry->getHash()),
                        new Arg($variableRegistry->getResult()),
                    ])
                )
            );
        }

        /*
         * We increase the depth of the context to allow to check the max depth of the mapping
         *
         * ```php
         * $context = MapperContext::withIncrementedDepth($context);
         * ```
         */
        $addedDependenciesStatements[] = new Stmt\Expression(
            new Expr\Assign(
                $variableRegistry->getContext(),
                new Expr\StaticCall(new Name\FullyQualified(MapperContext::class), 'withIncrementedDepth', [
                    new Arg($variableRegistry->getContext()),
                ])
            )
        );

        return $addedDependenciesStatements;
    }
}
