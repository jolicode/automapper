<?php

declare(strict_types=1);

namespace AutoMapper\Generator;

use AutoMapper\Exception\ReadOnlyTargetException;
use AutoMapper\Extractor\CustomTransformerExtractor;
use AutoMapper\Generator\Shared\CachedReflectionStatementsGenerator;
use AutoMapper\Generator\Shared\DiscriminatorStatementsGenerator;
use AutoMapper\MapperContext;
use AutoMapper\MapperGeneratorMetadataInterface;
use PhpParser\Node\Arg;
use PhpParser\Node\Expr;
use PhpParser\Node\Name;
use PhpParser\Node\Scalar;
use PhpParser\Node\Stmt;

/**
 * @internal
 */
final readonly class MapMethodStatementsGenerator
{
    private CreateTargetStatementsGenerator $createObjectStatementsGenerator;
    private PropertyStatementsGenerator $propertyStatementsGenerator;

    public function __construct(
        DiscriminatorStatementsGenerator $discriminatorStatementsGenerator,
        CachedReflectionStatementsGenerator $cachedReflectionStatementsGenerator,
        CustomTransformerExtractor $customTransformerExtractor,
        private bool $allowReadOnlyTargetToPopulate = false,
    ) {
        $this->createObjectStatementsGenerator = new CreateTargetStatementsGenerator(
            $discriminatorStatementsGenerator,
            $cachedReflectionStatementsGenerator,
            $customTransformerExtractor
        );
        $this->propertyStatementsGenerator = new PropertyStatementsGenerator($customTransformerExtractor);
    }

    /**
     * @return list<Stmt>
     */
    public function getStatements(MapperGeneratorMetadataInterface $mapperMetadata): array
    {
        $variableRegistry = $mapperMetadata->getVariableRegistry();

        $statements = [$this->ifSourceIsNullReturnNull($mapperMetadata)];
        $statements = [...$statements, ...$this->handleCircularReference($mapperMetadata)];
        $statements = [...$statements, ...$this->initializeTargetToPopulate($mapperMetadata)];
        $statements[] = $this->createObjectStatementsGenerator->generate($mapperMetadata, $variableRegistry);

        $addedDependenciesStatements = $this->handleDependencies($mapperMetadata);

        $duplicatedStatements = [];
        $setterStatements = [];
        foreach ($mapperMetadata->getPropertiesMapping() as $propertyMapping) {
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
            $propStatements = $this->propertyStatementsGenerator->generate($propertyMapping);

            /*
             * Dispatch those statements into two categories:
             * - Statements that need to be executed before the constructor, if the property needs to be written in the constructor
             * - Statements that need to be executed after the constructor.
             */
            if (\in_array($propertyMapping->property, $mapperMetadata->getPropertiesInConstructor(), true)) {
                $duplicatedStatements = [...$duplicatedStatements, ...$propStatements];
            } else {
                $setterStatements = [...$setterStatements, ...$propStatements];
            }
        }

        if (\count($duplicatedStatements) > 0 && \count($mapperMetadata->getPropertiesInConstructor())) {
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

        return [
            ...$statements,
            ...$setterStatements,
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
    private function ifSourceIsNullReturnNull(MapperGeneratorMetadataInterface $mapperMetadata): Stmt
    {
        $variableRegistry = $mapperMetadata->getVariableRegistry();

        return new Stmt\If_(
            new Expr\BinaryOp\Identical(new Expr\ConstFetch(new Name('null')), $variableRegistry->getSourceInput()),
            [
                'stmts' => [new Stmt\Return_($variableRegistry->getSourceInput())],
            ]
        );
    }

    /**
     * When there can be circular dependency in the mapping,
     * the following statements try to use the reference for the source if it's available.
     *
     * ```php
     * $sourceHash = spl_object_hash($source) . $target;
     * if (MapperContext::shouldHandleCircularReference($context, $sourceHash, $source)) {
     *     return MapperContext::handleCircularReference($context, $sourceHash, $source, $this->circularReferenceLimit, $this->circularReferenceHandler);
     * }
     * ```
     *
     * @return list<Stmt>
     */
    private function handleCircularReference(MapperGeneratorMetadataInterface $mapperMetadata): array
    {
        if (!$mapperMetadata->canHaveCircularReference()) {
            return [];
        }

        $variableRegistry = $mapperMetadata->getVariableRegistry();

        return [
            new Stmt\Expression(
                new Expr\Assign(
                    $variableRegistry->getHash(),
                    new Expr\BinaryOp\Concat(new Expr\FuncCall(new Name('spl_object_hash'), [
                        new Arg($variableRegistry->getSourceInput()),
                    ]),
                        new Scalar\String_($mapperMetadata->getTarget())
                    )
                )
            ),
            new Stmt\If_(
                new Expr\StaticCall(new Name\FullyQualified(MapperContext::class), 'shouldHandleCircularReference', [
                    new Arg($variableRegistry->getContext()),
                    new Arg($variableRegistry->getHash()),
                    new Arg(new Expr\PropertyFetch(new Expr\Variable('this'), 'circularReferenceLimit')),
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
                                    new Arg(
                                        new Expr\PropertyFetch(new Expr\Variable('this'), 'circularReferenceLimit')
                                    ),
                                    new Arg(
                                        new Expr\PropertyFetch(new Expr\Variable('this'), 'circularReferenceHandler')
                                    ),
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
    private function initializeTargetToPopulate(MapperGeneratorMetadataInterface $mapperMetadata): array
    {
        $variableRegistry = $mapperMetadata->getVariableRegistry();
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
            )
        );

        if (!$this->allowReadOnlyTargetToPopulate && $mapperMetadata->isTargetReadOnlyClass()) {
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
    private function handleDependencies(MapperGeneratorMetadataInterface $mapperMetadata): array
    {
        if (!$mapperMetadata->getAllDependencies()) {
            return [];
        }

        $variableRegistry = $mapperMetadata->getVariableRegistry();

        $addedDependenciesStatements = [];
        if ($mapperMetadata->canHaveCircularReference()) {
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
