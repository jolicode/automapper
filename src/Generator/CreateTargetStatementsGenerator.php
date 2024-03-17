<?php

declare(strict_types=1);

namespace AutoMapper\Generator;

use AutoMapper\Exception\MissingConstructorArgumentsException;
use AutoMapper\Generator\Shared\CachedReflectionStatementsGenerator;
use AutoMapper\Generator\Shared\DiscriminatorStatementsGenerator;
use AutoMapper\Lazy\LazyMap;
use AutoMapper\MapperContext;
use AutoMapper\Metadata\GeneratorMetadata;
use AutoMapper\Metadata\PropertyMetadata;
use AutoMapper\Transformer\AllowNullValueTransformerInterface;
use PhpParser\Node\Arg;
use PhpParser\Node\ClosureUse;
use PhpParser\Node\Expr;
use PhpParser\Node\Name;
use PhpParser\Node\Param;
use PhpParser\Node\Scalar;
use PhpParser\Node\StaticVar;
use PhpParser\Node\Stmt;

use function AutoMapper\PhpParser\create_expr_array_item;
use function AutoMapper\PhpParser\create_scalar_int;

/**
 * @internal
 */
final readonly class CreateTargetStatementsGenerator
{
    public function __construct(
        private DiscriminatorStatementsGenerator $discriminatorStatementsGeneratorSource,
        private DiscriminatorStatementsGenerator $discriminatorStatementsGeneratorTarget,
        private CachedReflectionStatementsGenerator $cachedReflectionStatementsGenerator,
    ) {
    }

    /**
     * If the result is null, we create the object.
     *
     * ```php
     * if (null === $result) {
     *    ... // create object statements
     * }
     * ```
     *
     * @return list<Stmt>
     */
    public function generate(GeneratorMetadata $metadata, VariableRegistry $variableRegistry, bool $callDoConstruct): array
    {
        $createObjectStatements = [];

        $createObjectStatements[] = $this->lazyLoadStatement($metadata, $variableRegistry, $callDoConstruct);
        $createObjectStatements[] = $this->targetAsArray($metadata);
        $createObjectStatements[] = $this->sourceAndTargetAsStdClass($metadata);
        $createObjectStatements[] = $this->targetAsStdClass($metadata);
        $createObjectStatements = [...$createObjectStatements, ...$this->discriminatorStatementsGeneratorSource->createTargetStatements($metadata)];
        $createObjectStatements = [...$createObjectStatements, ...$this->discriminatorStatementsGeneratorTarget->createTargetStatements($metadata)];
        $createObjectStatements[] = $this->cachedReflectionStatementsGenerator->createTargetStatement($metadata);
        $createObjectStatements[] = $this->constructorWithoutArgument($metadata);

        if ($callDoConstruct) {
            $createObjectStatements[] = $this->doConstructStatement($variableRegistry);
        }

        $createObjectStatements = array_values(array_filter($createObjectStatements));

        if ($this->canUseTargetToPopulate($metadata)) {
            return [new Stmt\If_(new Expr\BinaryOp\Identical(new Expr\ConstFetch(new Name('null')), $variableRegistry->getResult()), [
                'stmts' => $createObjectStatements,
            ])];
        }

        return $createObjectStatements;
    }

    public function canUseTargetToPopulate(GeneratorMetadata $metadata): bool
    {
        return !$this->discriminatorStatementsGeneratorTarget->supports($metadata);
    }

    private function lazyLoadStatement(GeneratorMetadata $metadata, VariableRegistry $variableRegistry, bool $callDoConstruct): Stmt
    {
        /** @var class-string<ClosureUse> $closureUseClass */
        $closureUseClass = class_exists(ClosureUse::class) ? ClosureUse::class : Arg::class;
        $doMapStmt = new Stmt\Expression(
            new Expr\MethodCall(
                new Expr\Variable('this'),
                'doMap',
                [
                    new Arg($variableRegistry->getSourceInput()),
                    new Arg($variableRegistry->getResult()),
                    new Arg($variableRegistry->getContext()),
                ],
            )
        );

        if ($metadata->mapperMetadata->target === 'array') {
            return new Stmt\If_(new Expr\StaticCall(new Name\FullyQualified(MapperContext::class), 'shouldLazyLoad', [
                new Arg($variableRegistry->getContext()),
            ]), [
                'stmts' => [
                    new Stmt\Expression(new Expr\Assign($variableRegistry->getResult(), new Expr\New_(
                        new Name\FullyQualified(LazyMap::class), [
                            new Arg(new Expr\Closure([
                                'params' => [
                                    new Param($variableRegistry->getResult(), byRef: true),
                                ],
                                'stmts' => [$doMapStmt],
                                'uses' => [
                                    new $closureUseClass($variableRegistry->getSourceInput()),
                                    new $closureUseClass($variableRegistry->getContext()),
                                ],
                            ])),
                        ]
                    ))),
                    new Stmt\Return_($variableRegistry->getResult()),
                ],
            ]);
        }

        $closureStatements = [];

        if ($callDoConstruct) {
            $closureStatements[] = $this->doConstructStatement($variableRegistry);
        }

        $closureStatements[] = $doMapStmt;

        /**
         * ```php
         * if (MapperContext::shouldLazyLoad($context)) {
         *     static $reflectionClass = new \ReflectionClass(Target::class);
         *     $result = $reflectionClass->newLazyGhost(static function (&$result) use ($source, $context) {
         *        $this->doConstruct($value, $result, $context);
         *        $this->doMap($value, $result, $context);
         *    });
         *    return $result;
         * }.
         */
        $reflectionClassVarName = $variableRegistry->getVariableWithUniqueName('reflectionClass');

        return new Stmt\If_(new Expr\StaticCall(new Name\FullyQualified(MapperContext::class), 'shouldLazyLoad', [
            new Arg($variableRegistry->getContext()),
        ]), [
            'stmts' => [
                new Stmt\Static_([
                    new StaticVar($reflectionClassVarName, new Expr\New_(new Name\FullyQualified(\ReflectionClass::class), [
                        new Arg(new Expr\ClassConstFetch(new Name\FullyQualified($metadata->mapperMetadata->target), 'class')),
                    ])),
                ]),
                new Stmt\Expression(new Expr\Assign($variableRegistry->getResult(), new Expr\MethodCall($reflectionClassVarName, 'newLazyGhost', [
                    new Arg(new Expr\Closure([
                        'params' => [
                            new Param($variableRegistry->getResult()),
                        ],
                        'stmts' => $closureStatements,
                        'uses' => [
                            new $closureUseClass($variableRegistry->getSourceInput()),
                            new $closureUseClass($variableRegistry->getContext()),
                        ],
                    ])),
                ]))),
                new Stmt\Return_($variableRegistry->getResult()),
            ],
        ]);
    }

    private function doConstructStatement(VariableRegistry $variableRegistry): Stmt
    {
        return new Stmt\Expression(
            new Expr\MethodCall(
                new Expr\Variable('this'),
                'doConstruct',
                [
                    new Arg($variableRegistry->getSourceInput()),
                    new Arg($variableRegistry->getResult()),
                    new Arg($variableRegistry->getContext()),
                ],
            )
        );
    }

    private function targetAsArray(GeneratorMetadata $metadata): ?Stmt
    {
        if ($metadata->mapperMetadata->target !== 'array') {
            return null;
        }

        return new Stmt\Expression(new Expr\Assign($metadata->variableRegistry->getResult(), new Expr\Array_()));
    }

    private function sourceAndTargetAsStdClass(GeneratorMetadata $metadata): ?Stmt
    {
        if (\stdClass::class !== $metadata->mapperMetadata->source || \stdClass::class !== $metadata->mapperMetadata->target) {
            return null;
        }

        return new Stmt\Expression(
            new Expr\Assign(
                $metadata->variableRegistry->getResult(),
                new Expr\FuncCall(
                    new Name('unserialize'),
                    [new Arg(new Expr\FuncCall(new Name('serialize'), [new Arg($metadata->variableRegistry->getSourceInput())]))]
                )
            )
        );
    }

    private function targetAsStdClass(GeneratorMetadata $metadata): ?Stmt
    {
        if (\stdClass::class === $metadata->mapperMetadata->source || \stdClass::class !== $metadata->mapperMetadata->target) {
            return null;
        }

        return new Stmt\Expression(new Expr\Assign($metadata->variableRegistry->getResult(), new Expr\New_(new Name(\stdClass::class))));
    }

    /**
     * @return list<Stmt>
     */
    public function getConstructStatements(GeneratorMetadata $metadata): array
    {
        if (!$metadata->isTargetUserDefined()) {
            return [];
        }

        $targetConstructor = $metadata->mapperMetadata->targetReflectionClass?->getConstructor();

        if (!$targetConstructor || !$metadata->hasConstructor()) {
            return [];
        }

        $createObjectStatements = [];
        $constructVar = $metadata->variableRegistry->getVariableWithUniqueName('constructArgs');

        foreach ($targetConstructor->getParameters() as $constructorParameter) {
            // Find property for parameter
            $propertyMetadata = $metadata->getTargetPropertyWithConstructor($constructorParameter->getName());

            $propertyStatements = null;
            $assignVar = new Expr\ArrayDimFetch(
                $constructVar,
                new Scalar\String_($constructorParameter->getName())
            );

            if (null !== $propertyMetadata) {
                $propertyStatements = $this->constructorArgument($assignVar, $metadata, $propertyMetadata, $constructorParameter);
            }

            if (null === $propertyStatements) {
                $propertyStatements = $this->constructorArgumentWithoutSource($assignVar, $metadata, $constructorParameter);
            }

            $createObjectStatements = [...$createObjectStatements, ...$propertyStatements];
        }

        $createObjectStatements = [
            new Stmt\Expression(new Expr\Assign($constructVar, new Expr\Array_())),
            ...$createObjectStatements,
        ];

        /*
         * Create object with named constructor arguments
         *
         * $result->__construct(foo: $constructArg1, bar: $constructArg2, ...); // If lazy ghost class is available
         */
        $createObjectStatements[] = new Stmt\Expression(
            new Expr\MethodCall(
                $metadata->variableRegistry->getResult(),
                '__construct',
                [new Arg($constructVar, unpack: true)],
            )
        );

        return $createObjectStatements;
    }

    /**
     * If source missing a constructor argument, check if there is a constructor argument in the context, otherwise we use the default value or throw exception.
     *
     * ```php
     *  if ($value is defined) {
     *      $constructarg['param'] = transformation of value
     *  } elseif (MapperContext::hasConstructorArgument($context, $target, 'propertyName')) {
     *      $constructarg['param'] = MapperContext::getConstructorArgument($context, $target, 'propertyName');
     *  } else {
     *      // throw exception if no default expression and no null allowed
     *  }
     * ```
     *
     * @return list<Stmt>|null
     */
    private function constructorArgument(Expr\ArrayDimFetch $assignVar, GeneratorMetadata $metadata, PropertyMetadata $propertyMetadata, \ReflectionParameter $parameter): ?array
    {
        $variableRegistry = $metadata->variableRegistry;
        $fieldValueExpr = $propertyMetadata->source->accessor?->getExpression($variableRegistry->getSourceInput());
        $conditionDefined = $propertyMetadata->source->accessor?->getIsDefinedExpression($variableRegistry->getSourceInput(), $parameter->allowsNull());

        if (null === $fieldValueExpr) {
            if (!($propertyMetadata->transformer instanceof AllowNullValueTransformerInterface)) {
                return null;
            }

            $fieldValueExpr = new Expr\ConstFetch(new Name('null'));
        }

        $defaultValueExpr = null;

        if (!$parameter->isDefaultValueAvailable()) {
            if ($parameter->allowsNull()) {
                $defaultValueExpr = new Expr\ConstFetch(new Name('null'));
            } else {
                $defaultValueExpr = new Expr\Throw_(new Expr\New_(new Name\FullyQualified(MissingConstructorArgumentsException::class), [
                    new Arg(new Scalar\String_(\sprintf('Cannot create an instance of "%s" from mapping data because its constructor requires the following parameters to be present : "$%s".', $metadata->mapperMetadata->target, $propertyMetadata->target->property))),
                    new Arg(create_scalar_int(0)),
                    new Arg(new Expr\ConstFetch(new Name('null'))),
                    new Arg(new Expr\Array_([
                        create_expr_array_item(new Scalar\String_($propertyMetadata->target->property)),
                    ])),
                    new Arg(new Scalar\String_($metadata->mapperMetadata->target)),
                ]));
            }
        }

        /* Get extract and transform statements for this property */
        [$output, $propStatements] = $propertyMetadata->transformer->transform($fieldValueExpr, $assignVar, $propertyMetadata, $variableRegistry->getUniqueVariableScope(), $variableRegistry->getSourceInput());

        $hasConstructorArgument = new Expr\StaticCall(new Name\FullyQualified(MapperContext::class), 'hasConstructorArgument', [
            new Arg($variableRegistry->getContext()),
            new Arg(new Scalar\String_($metadata->mapperMetadata->target)),
            new Arg(new Scalar\String_($propertyMetadata->target->property)),
        ]);
        $hasConstructorArgumentStmts = [
            new Stmt\Expression(new Expr\Assign($assignVar, new Expr\StaticCall(new Name\FullyQualified(MapperContext::class), 'getConstructorArgument', [
                new Arg($variableRegistry->getContext()),
                new Arg(new Scalar\String_($metadata->mapperMetadata->target)),
                new Arg(new Scalar\String_($propertyMetadata->target->property)),
            ]))),
        ];

        if (!$conditionDefined) {
            return [
                ...$propStatements,
                new Stmt\Expression(new Expr\Assign($assignVar, $output)),
            ];
        }

        $if = new Stmt\If_(
            $conditionDefined,
            [
                'stmts' => [
                    ...$propStatements,
                    new Stmt\Expression(new Expr\Assign($assignVar, $output)),
                ],
                'elseifs' => [
                    new Stmt\ElseIf_(
                        $hasConstructorArgument,
                        $hasConstructorArgumentStmts,
                    ),
                ],
            ],
        );

        if ($defaultValueExpr) {
            $if->else = new Stmt\Else_([
                new Stmt\Expression(new Expr\Assign($assignVar, $defaultValueExpr)),
            ]);
        }

        return [
            $if,
        ];
    }

    /**
     * Check if there is a constructor argument in the context, otherwise we use the default value or throw exception.
     *
     * ```php
     *  $constructarg = MapperContext::hasConstructorArgument($context, $target, 'propertyName')
     *      ? MapperContext::getConstructorArgument($context, $target, 'propertyName')
     *      : {defaultValueExpr} // default value or throw exception
     * ```
     *  ```php
     *   if (MapperContext::hasConstructorArgument($context, $target, 'propertyName')) {}
     *       $constructArgs['paramName'] = MapperContext::getConstructorArgument($context, $target, 'propertyName');
     *   } else {
     *       // throw exception if no default expression and no null allowed
     *       throw new MissingConstructorArgumentsException('Cannot create an instance of "AutoMapper\Tests\Fixtures\ConstructorWithDefaultValuesAsObjects" from mapping data because its constructor requires the following parameters to be present : "$baz".', 0, null, ['baz'], 'AutoMapper\Tests\Fixtures\ConstructorWithDefaultValuesAsObjects');
     *       // set null if no default expression and null allowed
     *       $constructArgs['paramName'] = null;
     *   }
     *  ```
     *
     * @return list<Stmt>
     */
    private function constructorArgumentWithoutSource(Expr\ArrayDimFetch $assignVar, GeneratorMetadata $metadata, \ReflectionParameter $constructorParameter): array
    {
        $variableRegistry = $metadata->variableRegistry;
        $defaultValueExpr = null;

        if (!$constructorParameter->isDefaultValueAvailable()) {
            if ($constructorParameter->allowsNull()) {
                $defaultValueExpr = new Expr\ConstFetch(new Name('null'));
            } else {
                $defaultValueExpr = new Expr\Throw_(new Expr\New_(new Name\FullyQualified(MissingConstructorArgumentsException::class), [
                    new Arg(new Scalar\String_(\sprintf('Cannot create an instance of "%s" from mapping data because its constructor requires the following parameters to be present : "$%s".', $metadata->mapperMetadata->target, $constructorParameter->getName()))),
                    new Arg(create_scalar_int(0)),
                    new Arg(new Expr\ConstFetch(new Name('null'))),
                    new Arg(new Expr\Array_([
                        create_expr_array_item(new Scalar\String_($constructorParameter->getName())),
                    ])),
                    new Arg(new Scalar\String_($constructorParameter->getName())),
                ]));
            }
        }

        $if = new Stmt\If_(
            new Expr\StaticCall(new Name\FullyQualified(MapperContext::class), 'hasConstructorArgument', [
                new Arg($variableRegistry->getContext()),
                new Arg(new Scalar\String_($metadata->mapperMetadata->target)),
                new Arg(new Scalar\String_($constructorParameter->getName())),
            ]),
            [
                'stmts' => [
                    new Stmt\Expression(new Expr\Assign($assignVar,
                        new Expr\StaticCall(new Name\FullyQualified(MapperContext::class), 'getConstructorArgument', [
                            new Arg($variableRegistry->getContext()),
                            new Arg(new Scalar\String_($metadata->mapperMetadata->target)),
                            new Arg(new Scalar\String_($constructorParameter->getName())),
                        ])
                    )),
                ],
            ]
        );

        if ($defaultValueExpr !== null) {
            $if->else = new Stmt\Else_([
                new Stmt\Expression(new Expr\Assign($assignVar, $defaultValueExpr)),
            ]);
        }

        return [
            $if,
        ];
    }

    /**
     * Create object with constructor (which have no arguments).
     *
     * ```php
     * $result = new Foo();
     * ```
     */
    private function constructorWithoutArgument(GeneratorMetadata $metadata): ?Stmt
    {
        if (!$metadata->isTargetUserDefined()
        ) {
            return null;
        }

        $targetConstructor = $metadata->mapperMetadata->targetReflectionClass?->getConstructor();

        if ($targetConstructor) {
            return null;
        }

        if ($metadata->mapperMetadata->targetReflectionClass?->isInstantiable() === false) {
            return null;
        }

        return new Stmt\Expression(new Expr\Assign($metadata->variableRegistry->getResult(), new Expr\New_(new Name\FullyQualified($metadata->mapperMetadata->target))));
    }
}
