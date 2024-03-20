<?php

declare(strict_types=1);

namespace AutoMapper\Generator;

use AutoMapper\Generator\Shared\CachedReflectionStatementsGenerator;
use AutoMapper\Generator\Shared\DiscriminatorStatementsGenerator;
use AutoMapper\MapperContext;
use AutoMapper\Metadata\GeneratorMetadata;
use AutoMapper\Metadata\PropertyMetadata;
use AutoMapper\Transformer\PropertyTransformer\PropertyTransformer;
use PhpParser\Node\Arg;
use PhpParser\Node\Expr;
use PhpParser\Node\Name;
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
    public function generate(GeneratorMetadata $metadata, VariableRegistry $variableRegistry): Stmt
    {
        $createObjectStatements = [];

        $createObjectStatements[] = $this->targetAsArray($metadata);
        $createObjectStatements[] = $this->sourceAndTargetAsStdClass($metadata);
        $createObjectStatements[] = $this->targetAsStdClass($metadata);
        $createObjectStatements = [...$createObjectStatements, ...$this->discriminatorStatementsGenerator->createTargetStatements($metadata)];
        $createObjectStatements = [...$createObjectStatements, ...$this->constructorArguments($metadata)];
        $createObjectStatements[] = $this->cachedReflectionStatementsGenerator->createTargetStatement($metadata);
        $createObjectStatements[] = $this->constructorWithoutArgument($metadata);

        $createObjectStatements = array_values(array_filter($createObjectStatements));

        return new Stmt\If_(new Expr\BinaryOp\Identical(new Expr\ConstFetch(new Name('null')), $variableRegistry->getResult()), [
            'stmts' => $createObjectStatements,
        ]);
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
    private function constructorArguments(GeneratorMetadata $metadata): array
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

        foreach ($metadata->propertiesMetadata as $propertyMetadata) {
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
            $constructorArgumentResult = $this->constructorArgument($metadata, $propertyMetadata);

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

            if (!$createObjectStatement) {
                continue;
            }

            $createObjectStatements[] = $createObjectStatement;
            $constructArguments[$constructorPosition] = $constructArgument;
        }

        ksort($constructArguments);

        /*
         * Create object with the constructor arguments
         *
         * $result = new Foo($constructArg1, $constructArg2, ...);
         */
        $createObjectStatements[] = new Stmt\Expression(
            new Expr\Assign(
                $metadata->variableRegistry->getResult(),
                new Expr\New_(new Name\FullyQualified($metadata->mapperMetadata->target), $constructArguments)
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
    private function constructorArgument(GeneratorMetadata $metadata, PropertyMetadata $propertyMetadata): ?array
    {
        if (null === $propertyMetadata->target->writeMutatorConstructor || null === ($parameter = $propertyMetadata->target->writeMutatorConstructor->parameter)) {
            return null;
        }

        $variableRegistry = $metadata->variableRegistry;
        $constructVar = $variableRegistry->getVariableWithUniqueName('constructArg');
        $fieldValueExpr = $propertyMetadata->source->accessor?->getExpression($variableRegistry->getSourceInput());

        if (null === $fieldValueExpr) {
            if (!($propertyMetadata->transformer instanceof PropertyTransformer)) {
                return null;
            }

            $fieldValueExpr = new Expr\ConstFetch(new Name('null'));
        }

        /* Get extract and transform statements for this property */
        [$output, $propStatements] = $propertyMetadata->transformer->transform($fieldValueExpr, $constructVar, $propertyMetadata, $variableRegistry->getUniqueVariableScope(), $variableRegistry->getSourceInput());

        $propStatements[] = new Stmt\Expression(new Expr\Assign($constructVar, $output));

        return [
            new Stmt\If_(new Expr\StaticCall(new Name\FullyQualified(MapperContext::class), 'hasConstructorArgument', [
                new Arg($variableRegistry->getContext()),
                new Arg(new Scalar\String_($metadata->mapperMetadata->target)),
                new Arg(new Scalar\String_($propertyMetadata->target->name)),
            ]), [
                'stmts' => [
                    new Stmt\Expression(new Expr\Assign($constructVar, new Expr\StaticCall(new Name\FullyQualified(MapperContext::class), 'getConstructorArgument', [
                        new Arg($variableRegistry->getContext()),
                        new Arg(new Scalar\String_($metadata->mapperMetadata->target)),
                        new Arg(new Scalar\String_($propertyMetadata->target->name)),
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
