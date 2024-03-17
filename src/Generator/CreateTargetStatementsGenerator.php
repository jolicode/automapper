<?php

declare(strict_types=1);

namespace AutoMapper\Generator;

use AutoMapper\Generator\Shared\CachedReflectionStatementsGenerator;
use AutoMapper\Generator\Shared\DiscriminatorStatementsGenerator;
use AutoMapper\MapperContext;
use AutoMapper\Metadata\GeneratorMetadata;
use AutoMapper\Metadata\PropertyMetadata;
use AutoMapper\Transformer\CustomTransformer\CustomPropertyTransformer;
use PhpParser\Node\Arg;
use PhpParser\Node\ClosureUse;
use PhpParser\Node\Expr;
use PhpParser\Node\Name;
use PhpParser\Node\Param;
use PhpParser\Node\Scalar;
use PhpParser\Node\Stmt;
use PhpParser\Parser;
use PhpParser\ParserFactory;

/**
 * @internal
 */
final readonly class CreateTargetStatementsGenerator
{
    private Parser $parser;

    public function __construct(
        private DiscriminatorStatementsGenerator $discriminatorStatementsGenerator,
        private CachedReflectionStatementsGenerator $cachedReflectionStatementsGenerator,
        ?Parser $parser = null,
    ) {
        $this->parser = $parser ?? (new ParserFactory())->createForHostVersion();
    }

    /**
     * If the result is null, we create the object.
     *
     * ```php
     * if (null === $result) {
     *    ... // create object statements
     * }
     * ```
     */
    public function generate(GeneratorMetadata $metadata, VariableRegistry $variableRegistry, bool $callDoConstruct): Stmt
    {
        $createObjectStatements = [];

        $createObjectStatements[] = $this->targetAsArray($metadata);
        $createObjectStatements[] = $this->sourceAndTargetAsStdClass($metadata);
        $createObjectStatements[] = $this->targetAsStdClass($metadata);
        $createObjectStatements = [...$createObjectStatements, ...$this->discriminatorStatementsGenerator->createTargetStatements($metadata)];
        $createObjectStatements[] = $this->lazyLoadStatement($metadata, $variableRegistry, $callDoConstruct);
        $createObjectStatements[] = $this->cachedReflectionStatementsGenerator->createTargetStatement($metadata);
        $createObjectStatements[] = $this->constructorWithoutArgument($metadata);

        if ($callDoConstruct) {
            $createObjectStatements[] = $this->doConstructStatement($variableRegistry);
        }

        $createObjectStatements = array_values(array_filter($createObjectStatements));

        return new Stmt\If_(new Expr\BinaryOp\Identical(new Expr\ConstFetch(new Name('null')), $variableRegistry->getResult()), [
            'stmts' => $createObjectStatements,
        ]);
    }

    private function lazyLoadStatement(GeneratorMetadata $metadata, VariableRegistry $variableRegistry, bool $callDoConstruct): ?Stmt
    {
        if ($metadata->mapperMetadata->lazyGhostClassName === null) {
            return null;
        }

        $closureStatements = [];

        if ($callDoConstruct) {
            $closureStatements[] = $this->doConstructStatement($variableRegistry);
        }

        $closureStatements[] = new Stmt\Expression(
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

        return new Stmt\If_(new Expr\StaticCall(new Name\FullyQualified(MapperContext::class), 'shouldLazyLoad', [
            new Arg($variableRegistry->getContext()),
        ]), [
            'stmts' => [
                new Stmt\Expression(new Expr\Assign($variableRegistry->getResult(), new Expr\StaticCall(new Name\FullyQualified($metadata->mapperMetadata->lazyGhostClassName), 'createLazyGhost', [
                    new Arg(new Expr\Closure([
                        'params' => [
                            new Param($variableRegistry->getResult()),
                        ],
                        'stmts' => $closureStatements,
                        'uses' => [
                            new ClosureUse($variableRegistry->getSourceInput()),
                            new ClosureUse($variableRegistry->getContext()),
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

        $constructArguments = [];
        $createObjectStatements = [];

        foreach ($metadata->propertiesMetadata as $propertyMapping) {
            /*
             * This is the main loop to map the properties from the source to the target in the constructor, there is 2 main steps in order to generated this code :
             *
             *  * Generate code on how to read the value from the source, which returns statements and an output expression
             *  * Generate code on how to transform the value, which use the output expression, add some statements and return a new output expression
             *
             * As an example this could generate the following code :
             *
             *  * Extract value from a private property : $this->extractCallbacks['propertyName']($source)
             *  * Transform the value, which is an object in this example, with another mapper : $this->mappers['SOURCE_TO_TARGET_MAPPER']->map(..., $context);
             *
             * The output expression of the transform will then be used as argument for the object constructor
             *
             * $constructArg1 = $this->mappers['SOURCE_TO_TARGET_MAPPER']->map($this->extractCallbacks['propertyName']($source), $context);
             * $result = new Foo($constructArg1);
             */
            $constructorArgumentResult = $this->constructorArgument($metadata, $propertyMapping);

            if (!$constructorArgumentResult) {
                continue;
            }

            [$createObjectStatement, $constructArgument, $constructorPosition] = $constructorArgumentResult;

            $createObjectStatements[] = $createObjectStatement;
            $constructArguments[$constructorPosition] = $constructArgument;
        }

        /* We loop to get constructor arguments that were not present in the source */
        foreach ($targetConstructor->getParameters() as $constructorParameter) {
            if (\array_key_exists($constructorParameter->getPosition(), $constructArguments) && $constructorParameter->isDefaultValueAvailable()) {
                continue;
            }

            [$createObjectStatement, $constructArgument, $constructorPosition] = $this->constructorArgumentWithDefaultValue($metadata, $constructArguments, $constructorParameter) ?? [null, null, null];

            if (!$createObjectStatement || !$constructArgument) {
                continue;
            }

            $createObjectStatements[] = $createObjectStatement;
            $constructArguments[$constructorPosition] = $constructArgument;
        }

        ksort($constructArguments);

        /*
         * Create object with the constructor arguments
         *
         * $result->__construct($constructArg1, $constructArg2, ...); // If lazy ghost class is available
         */
        $createObjectStatements[] = new Stmt\Expression(
            new Expr\MethodCall(
                $metadata->variableRegistry->getResult(),
                '__construct',
                $constructArguments,
            )
        );

        return $createObjectStatements;
    }

    /**
     * Check if there is a constructor argument in the context, otherwise we use the transformed value.
     *
     * ```php
     *  if (MapperContext::hasConstructorArgument($context, $target, 'propertyName')) {
     *     $constructArg1 = MapperContext::getConstructorArgument($context, $target, 'propertyName');
     *  } else {
     *     $constructArg1 = $source->propertyName;
     *  }
     * ```
     *
     * @return array{Stmt, Arg, int}|null
     */
    private function constructorArgument(GeneratorMetadata $metadata, PropertyMetadata $propertyMapping): ?array
    {
        if (null === $propertyMapping->target->writeMutatorConstructor || null === ($parameter = $propertyMapping->target->writeMutatorConstructor->parameter)) {
            return null;
        }

        $variableRegistry = $metadata->variableRegistry;
        $constructVar = $variableRegistry->getVariableWithUniqueName('constructArg');
        $fieldValueExpr = $propertyMapping->source->accessor?->getExpression($variableRegistry->getSourceInput());

        if (null === $fieldValueExpr) {
            if (!($propertyMapping->transformer instanceof CustomPropertyTransformer)) {
                return null;
            }

            $fieldValueExpr = $variableRegistry->getSourceInput();
        }

        /* Get extract and transform statements for this property */
        [$output, $propStatements] = $propertyMapping->transformer->transform($fieldValueExpr, $constructVar, $propertyMapping, $variableRegistry->getUniqueVariableScope(), $variableRegistry->getSourceInput());

        $propStatements[] = new Stmt\Expression(new Expr\Assign($constructVar, $output));

        return [
            new Stmt\If_(new Expr\StaticCall(new Name\FullyQualified(MapperContext::class), 'hasConstructorArgument', [
                new Arg($variableRegistry->getContext()),
                new Arg(new Scalar\String_($metadata->mapperMetadata->target)),
                new Arg(new Scalar\String_($propertyMapping->target->name)),
            ]), [
                'stmts' => [
                    new Stmt\Expression(new Expr\Assign($constructVar, new Expr\StaticCall(new Name\FullyQualified(MapperContext::class), 'getConstructorArgument', [
                        new Arg($variableRegistry->getContext()),
                        new Arg(new Scalar\String_($metadata->mapperMetadata->target)),
                        new Arg(new Scalar\String_($propertyMapping->target->name)),
                    ]))),
                ],
                'else' => new Stmt\Else_($propStatements),
            ]),
            new Arg($constructVar),
            $parameter->getPosition(),
        ];
    }

    /**
     * Check if there is a constructor argument in the context, otherwise we use the default value.
     *
     * ```
     * if (MapperContext::hasConstructorArgument($context, $target, 'propertyName')) {
     *     $constructArg2 = MapperContext::getConstructorArgument($context, $target, 'propertyName');
     * } else {
     *     $constructArg2 = 'default value';
     * }
     * ```
     *
     * @param Arg[] $constructArguments
     *
     * @return array{Stmt, Arg, int}|null
     */
    private function constructorArgumentWithDefaultValue(GeneratorMetadata $metadata, array $constructArguments, \ReflectionParameter $constructorParameter): ?array
    {
        if (\array_key_exists($constructorParameter->getPosition(), $constructArguments) || !$constructorParameter->isDefaultValueAvailable()) {
            return null;
        }

        $variableRegistry = $metadata->variableRegistry;
        $constructVar = $variableRegistry->getVariableWithUniqueName('constructArg');

        return [
            new Stmt\If_(new Expr\StaticCall(new Name\FullyQualified(MapperContext::class), 'hasConstructorArgument', [
                new Arg($variableRegistry->getContext()),
                new Arg(new Scalar\String_($metadata->mapperMetadata->target)),
                new Arg(new Scalar\String_($constructorParameter->getName())),
            ]), [
                'stmts' => [
                    new Stmt\Expression(new Expr\Assign($constructVar, new Expr\StaticCall(new Name\FullyQualified(MapperContext::class), 'getConstructorArgument', [
                        new Arg($variableRegistry->getContext()),
                        new Arg(new Scalar\String_($metadata->mapperMetadata->target)),
                        new Arg(new Scalar\String_($constructorParameter->getName())),
                    ]))),
                ],
                'else' => new Stmt\Else_([
                    new Stmt\Expression(new Expr\Assign($constructVar, $this->getValueAsExpr($constructorParameter->getDefaultValue()))),
                ]),
            ]),
            new Arg($constructVar),
            $constructorParameter->getPosition(),
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

        if ($metadata->mapperMetadata->lazyGhostClassName !== null) {
            return null;
        }

        $targetConstructor = $metadata->mapperMetadata->targetReflectionClass?->getConstructor();

        if ($targetConstructor) {
            return null;
        }

        return new Stmt\Expression(new Expr\Assign($metadata->variableRegistry->getResult(), new Expr\New_(new Name\FullyQualified($metadata->mapperMetadata->target))));
    }

    private function getValueAsExpr(mixed $value): Expr
    {
        $expr = $this->parser->parse('<?php ' . var_export($value, true) . ';')[0] ?? null;

        if ($expr instanceof Stmt\Expression) {
            return $expr->expr;
        }

        throw new \LogicException('Cannot extract expr from ' . var_export($value, true));
    }
}
