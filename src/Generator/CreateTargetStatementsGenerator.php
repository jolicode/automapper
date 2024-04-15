<?php

declare(strict_types=1);

namespace AutoMapper\Generator;

use AutoMapper\Exception\LogicException;
use AutoMapper\Exception\MissingConstructorArgumentsException;
use AutoMapper\Generator\Shared\CachedReflectionStatementsGenerator;
use AutoMapper\Generator\Shared\DiscriminatorStatementsGenerator;
use AutoMapper\MapperContext;
use AutoMapper\Metadata\GeneratorMetadata;
use AutoMapper\Metadata\PropertyMetadata;
use AutoMapper\Transformer\AllowNullValueTransformerInterface;
use PhpParser\Node\Arg;
use PhpParser\Node\ArrayItem;
use PhpParser\Node\Expr;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PhpParser\Node\Scalar;
use PhpParser\Node\Stmt;
use PhpParser\Parser;
use PhpParser\ParserFactory;

// compatibility with nikic/php-parser 4.x
if (!class_exists(ArrayItem::class) && class_exists(Expr\ArrayItem::class)) {
    class_alias(Expr\ArrayItem::class, ArrayItem::class);
}

/**
 * @internal
 */
final readonly class CreateTargetStatementsGenerator
{
    private Parser $parser;

    public function __construct(
        private DiscriminatorStatementsGenerator $discriminatorStatementsGeneratorSource,
        private DiscriminatorStatementsGenerator $discriminatorStatementsGeneratorTarget,
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
        $createObjectStatements = [...$createObjectStatements, ...$this->discriminatorStatementsGeneratorSource->createTargetStatements($metadata)];
        $createObjectStatements = [...$createObjectStatements, ...$this->discriminatorStatementsGeneratorTarget->createTargetStatements($metadata)];
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

        foreach ($targetConstructor->getParameters() as $constructorParameter) {
            // Find property for parameter
            $propertyMetadata = $metadata->getTargetPropertyWithConstructor($constructorParameter->getName());

            $createObjectStatement = null;
            $constructArgument = null;
            $constructorName = null;

            if (null !== $propertyMetadata) {
                [$createObjectStatement, $constructArgument, $constructorName] = $this->constructorArgument($metadata, $propertyMetadata, $constructorParameter);
            }

            if (null === $createObjectStatement || null === $constructArgument || null === $constructorName) {
                [$createObjectStatement, $constructArgument, $constructorName] = $this->constructorArgumentWithoutSource($metadata, $constructorParameter);
            }

            $createObjectStatements[] = $createObjectStatement;
            $constructArguments[$constructorName] = $constructArgument;
        }

        /*
         * Create object with named constructor arguments
         *
         * $result = new Foo(foo: $constructArg1, bar: $constructArg2, ...);
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
     *     $constructArg1 = $source->propertyName ?? MapperContext::getConstructorArgument($context, $target, 'propertyName');
     *  } else {
     *     $constructArg1 = $source->propertyName;
     *  }
     * ```
     *
     * @return array{Stmt, Arg, string}|array{null, null, null}
     */
    private function constructorArgument(GeneratorMetadata $metadata, PropertyMetadata $propertyMetadata, \ReflectionParameter $parameter): array
    {
        $variableRegistry = $metadata->variableRegistry;
        $constructVar = $variableRegistry->getVariableWithUniqueName('constructArg');
        $fieldValueExpr = $propertyMetadata->source->accessor?->getExpression($variableRegistry->getSourceInput());

        if (null === $fieldValueExpr) {
            if (!($propertyMetadata->transformer instanceof AllowNullValueTransformerInterface)) {
                return [null, null, null];
            }

            $fieldValueExpr = new Expr\ConstFetch(new Name('null'));
        }

        /* Get extract and transform statements for this property */
        [$output, $propStatements] = $propertyMetadata->transformer->transform($fieldValueExpr, $constructVar, $propertyMetadata, $variableRegistry->getUniqueVariableScope(), $variableRegistry->getSourceInput());

        $defaultValueExpr = new Expr\Throw_(
            new Expr\New_(new Name\FullyQualified(MissingConstructorArgumentsException::class), [
                new Arg(new Scalar\String_(sprintf('Cannot create an instance of "%s" from mapping data because its constructor requires the following parameters to be present : "$%s".', $metadata->mapperMetadata->target, $propertyMetadata->target->name))),
                new Arg(new Scalar\LNumber(0)),
                new Arg(new Expr\ConstFetch(new Name('null'))),
                new Arg(new Expr\Array_([
                    new ArrayItem(new Scalar\String_($propertyMetadata->target->name)),
                ])),
                new Arg(new Scalar\String_($metadata->mapperMetadata->target)),
            ])
        );

        if ($parameter->isDefaultValueAvailable()) {
            $defaultValueExpr = $this->getValueAsExpr($parameter->getDefaultValue());
        } elseif ($parameter->allowsNull()) {
            $defaultValueExpr = new Expr\ConstFetch(new Name('null'));
        }

        return [
            new Stmt\If_(new Expr\StaticCall(new Name\FullyQualified(MapperContext::class), 'hasConstructorArgument', [
                new Arg($variableRegistry->getContext()),
                new Arg(new Scalar\String_($metadata->mapperMetadata->target)),
                new Arg(new Scalar\String_($propertyMetadata->target->name)),
            ]), [
                'stmts' => [
                    ...$propStatements,
                    new Stmt\Expression(new Expr\Assign($constructVar, new Expr\BinaryOp\Coalesce($output, new Expr\StaticCall(new Name\FullyQualified(MapperContext::class), 'getConstructorArgument', [
                        new Arg($variableRegistry->getContext()),
                        new Arg(new Scalar\String_($metadata->mapperMetadata->target)),
                        new Arg(new Scalar\String_($propertyMetadata->target->name)),
                    ])))),
                ],
                'else' => new Stmt\Else_([
                    ...$propStatements,
                    new Stmt\Expression(new Expr\Assign($constructVar, new Expr\BinaryOp\Coalesce($output, $defaultValueExpr))),
                ]),
            ]),
            new Arg($constructVar, name: new Identifier($parameter->getName())),
            $parameter->getName(),
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
     *     // or set to null if the parameter is nullable
     *     $constructArg2 = null;
     *     // throw an exception otherwise
     *     throw new MissingConstructorArgumentsException('Cannot create an instance of "Foo" from mapping data because its constructor requires the following parameters to be present : "$propertyName".', 0, null, ['propertyName'], 'Foo');
     * }
     * ```
     *
     * @return array{Stmt, Arg, string}
     */
    private function constructorArgumentWithoutSource(GeneratorMetadata $metadata, \ReflectionParameter $constructorParameter): array
    {
        $variableRegistry = $metadata->variableRegistry;
        $constructVar = $variableRegistry->getVariableWithUniqueName('constructArg');

        $defaultValueExpr = new Expr\Throw_(new Expr\New_(new Name\FullyQualified(MissingConstructorArgumentsException::class), [
            new Arg(new Scalar\String_(sprintf('Cannot create an instance of "%s" from mapping data because its constructor requires the following parameters to be present : "$%s".', $metadata->mapperMetadata->target, $constructorParameter->getName()))),
            new Arg(new Scalar\LNumber(0)),
            new Arg(new Expr\ConstFetch(new Name('null'))),
            new Arg(new Expr\Array_([
                new ArrayItem(new Scalar\String_($constructorParameter->getName())),
            ])),
            new Arg(new Scalar\String_($constructorParameter->getName())),
        ]));

        if ($constructorParameter->isDefaultValueAvailable()) {
            $defaultValueExpr = new Expr\Assign($constructVar, $this->getValueAsExpr($constructorParameter->getDefaultValue()));
        } elseif ($constructorParameter->allowsNull()) {
            $defaultValueExpr = new Expr\Assign($constructVar, new Expr\ConstFetch(new Name('null')));
        }

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
                    new Stmt\Expression($defaultValueExpr),
                ]),
            ]),
            new Arg($constructVar, name: new Identifier($constructorParameter->getName())),
            $constructorParameter->getName(),
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

        throw new LogicException('Cannot extract expr from ' . var_export($value, true));
    }
}
