<?php

declare(strict_types=1);

namespace AutoMapper\Generator;

use AutoMapper\Extractor\PropertyMapping;
use AutoMapper\Generator\Shared\CachedReflectionStatementsGenerator;
use AutoMapper\Generator\Shared\DiscriminatorStatementsGenerator;
use AutoMapper\MapperContext;
use AutoMapper\MapperGeneratorMetadataInterface;
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
        $this->parser = $parser ?? (new ParserFactory())->create(ParserFactory::PREFER_PHP7);
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
    public function generate(MapperGeneratorMetadataInterface $mapperMetadata, VariableRegistry $variableRegistry): Stmt
    {
        $createObjectStatements = [];

        $createObjectStatements[] = $this->targetAsArray($mapperMetadata);
        $createObjectStatements[] = $this->sourceAndTargetAsStdClass($mapperMetadata);
        $createObjectStatements[] = $this->targetAsStdClass($mapperMetadata);
        $createObjectStatements = [...$createObjectStatements, ...$this->discriminatorStatementsGenerator->createTargetStatements($mapperMetadata)];
        $createObjectStatements = [...$createObjectStatements, ...$this->constructorArguments($mapperMetadata)];
        $createObjectStatements[] = $this->cachedReflectionStatementsGenerator->createTargetStatement($mapperMetadata);
        $createObjectStatements[] = $this->constructorWithoutArgument($mapperMetadata);

        $createObjectStatements = array_values(array_filter($createObjectStatements));

        return new Stmt\If_(new Expr\BinaryOp\Identical(new Expr\ConstFetch(new Name('null')), $variableRegistry->getResult()), [
            'stmts' => $createObjectStatements,
        ]);
    }

    private function targetAsArray(MapperGeneratorMetadataInterface $mapperMetadata): ?Stmt
    {
        if ($mapperMetadata->getTarget() !== 'array') {
            return null;
        }

        $variableRegistry = $mapperMetadata->getVariableRegistry();

        return new Stmt\Expression(new Expr\Assign($variableRegistry->getResult(), new Expr\Array_()));
    }

    private function sourceAndTargetAsStdClass(MapperGeneratorMetadataInterface $mapperMetadata): ?Stmt
    {
        if (\stdClass::class !== $mapperMetadata->getSource() || \stdClass::class !== $mapperMetadata->getTarget()) {
            return null;
        }

        $variableRegistry = $mapperMetadata->getVariableRegistry();

        return new Stmt\Expression(
            new Expr\Assign(
                $variableRegistry->getResult(),
                new Expr\FuncCall(
                    new Name('unserialize'),
                    [new Arg(new Expr\FuncCall(new Name('serialize'), [new Arg($variableRegistry->getSourceInput())]))]
                )
            )
        );
    }

    private function targetAsStdClass(MapperGeneratorMetadataInterface $mapperMetadata): ?Stmt
    {
        if (\stdClass::class === $mapperMetadata->getSource() || \stdClass::class !== $mapperMetadata->getTarget()) {
            return null;
        }

        $variableRegistry = $mapperMetadata->getVariableRegistry();

        return new Stmt\Expression(new Expr\Assign($variableRegistry->getResult(), new Expr\New_(new Name(\stdClass::class))));
    }

    /**
     * @return list<Stmt>
     */
    private function constructorArguments(MapperGeneratorMetadataInterface $mapperMetadata): array
    {
        if (!$mapperMetadata->targetIsAUserDefinedClass()) {
            return [];
        }

        $targetConstructor = $mapperMetadata->getCachedTargetReflectionClass()?->getConstructor();

        if (!$targetConstructor || !$mapperMetadata->hasConstructor()) {
            return [];
        }

        $constructArguments = [];
        $createObjectStatements = [];

        foreach ($mapperMetadata->getPropertiesMapping() as $propertyMapping) {
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
            $constructorArgumentResult = $this->constructorArgument($propertyMapping);

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

            [$createObjectStatement, $constructArgument, $constructorPosition] = $this->constructorArgumentWithDefaultValue($mapperMetadata, $constructArguments, $constructorParameter) ?? [null, null, null];

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
                $mapperMetadata->getVariableRegistry()->getResult(),
                new Expr\New_(new Name\FullyQualified($mapperMetadata->getTarget()), $constructArguments)
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
    private function constructorArgument(PropertyMapping $propertyMapping): ?array
    {
        if (null === $propertyMapping->writeMutatorConstructor || null === ($parameter = $propertyMapping->writeMutatorConstructor->parameter)) {
            return null;
        }

        $mapperMetadata = $propertyMapping->mapperMetadata;
        $variableRegistry = $mapperMetadata->getVariableRegistry();

        $constructVar = $variableRegistry->getVariableWithUniqueName('constructArg');

        if ($propertyMapping->readAccessor) {
            $fieldValueExpr = $propertyMapping->readAccessor->getExpression($variableRegistry->getSourceInput());

            /* Get extract and transform statements for this property */
            [$output, $propStatements] = $propertyMapping->transformer->transform($fieldValueExpr, $constructVar, $propertyMapping, $variableRegistry->getUniqueVariableScope(), $variableRegistry->getSourceInput());
        } else {
            return null;
        }

        $propStatements[] = new Stmt\Expression(new Expr\Assign($constructVar, $output));

        return [
            new Stmt\If_(new Expr\StaticCall(new Name\FullyQualified(MapperContext::class), 'hasConstructorArgument', [
                new Arg($variableRegistry->getContext()),
                new Arg(new Scalar\String_($mapperMetadata->getTarget())),
                new Arg(new Scalar\String_($propertyMapping->property)),
            ]), [
                'stmts' => [
                    new Stmt\Expression(new Expr\Assign($constructVar, new Expr\StaticCall(new Name\FullyQualified(MapperContext::class), 'getConstructorArgument', [
                        new Arg($variableRegistry->getContext()),
                        new Arg(new Scalar\String_($mapperMetadata->getTarget())),
                        new Arg(new Scalar\String_($propertyMapping->property)),
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
    private function constructorArgumentWithDefaultValue(MapperGeneratorMetadataInterface $mapperMetadata, array $constructArguments, \ReflectionParameter $constructorParameter): ?array
    {
        if (\array_key_exists($constructorParameter->getPosition(), $constructArguments) || !$constructorParameter->isDefaultValueAvailable()) {
            return null;
        }

        $variableRegistry = $mapperMetadata->getVariableRegistry();

        $constructVar = $variableRegistry->getVariableWithUniqueName('constructArg');

        return [
            new Stmt\If_(new Expr\StaticCall(new Name\FullyQualified(MapperContext::class), 'hasConstructorArgument', [
                new Arg($variableRegistry->getContext()),
                new Arg(new Scalar\String_($mapperMetadata->getTarget())),
                new Arg(new Scalar\String_($constructorParameter->getName())),
            ]), [
                'stmts' => [
                    new Stmt\Expression(new Expr\Assign($constructVar, new Expr\StaticCall(new Name\FullyQualified(MapperContext::class), 'getConstructorArgument', [
                        new Arg($variableRegistry->getContext()),
                        new Arg(new Scalar\String_($mapperMetadata->getTarget())),
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
    private function constructorWithoutArgument(MapperGeneratorMetadataInterface $mapperMetadata): ?Stmt
    {
        if (!$mapperMetadata->targetIsAUserDefinedClass()
        ) {
            return null;
        }

        $targetConstructor = $mapperMetadata->getCachedTargetReflectionClass()?->getConstructor();

        if ($targetConstructor) {
            return null;
        }

        $variableRegistry = $mapperMetadata->getVariableRegistry();

        return new Stmt\Expression(new Expr\Assign($variableRegistry->getResult(), new Expr\New_(new Name\FullyQualified($mapperMetadata->getTarget()))));
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
