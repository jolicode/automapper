<?php

declare(strict_types=1);

namespace AutoMapper\Generator;

use AutoMapper\AutoMapperRegistryInterface;
use AutoMapper\Configuration;
use AutoMapper\Exception\CompileException;
use AutoMapper\Exception\InvalidMappingException;
use AutoMapper\GeneratedMapper;
use AutoMapper\Generator\Shared\CachedReflectionStatementsGenerator;
use AutoMapper\Generator\Shared\ClassDiscriminatorResolver;
use AutoMapper\Generator\Shared\DiscriminatorStatementsGenerator;
use AutoMapper\Metadata\GeneratorMetadata;
use PhpParser\Builder;
use PhpParser\Node\Expr;
use PhpParser\Node\Name;
use PhpParser\Node\Param;
use PhpParser\Node\Stmt;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;

use function AutoMapper\PhpParser\create_declare_item;
use function AutoMapper\PhpParser\create_scalar_int;

/**
 * Generates code for a mapping class.
 *
 * @author Joel Wurtz <jwurtz@jolicode.com>
 *
 * @internal
 */
final readonly class MapperGenerator
{
    private MapperConstructorGenerator $mapperConstructorGenerator;
    private InjectMapperMethodStatementsGenerator $injectMapperMethodStatementsGenerator;
    private MapMethodStatementsGenerator $mapMethodStatementsGenerator;
    private IdentifierHashGenerator $identifierHashGenerator;
    private bool $disableGeneratedMapper;

    public function __construct(
        ClassDiscriminatorResolver $classDiscriminatorResolver,
        Configuration $configuration,
        ExpressionLanguage $expressionLanguage,
    ) {
        $this->mapperConstructorGenerator = new MapperConstructorGenerator(
            $cachedReflectionStatementsGenerator = new CachedReflectionStatementsGenerator()
        );

        $this->mapMethodStatementsGenerator = new MapMethodStatementsGenerator(
            new DiscriminatorStatementsGenerator($classDiscriminatorResolver, true),
            new DiscriminatorStatementsGenerator($classDiscriminatorResolver, false),
            $cachedReflectionStatementsGenerator,
            $expressionLanguage,
        );

        $this->injectMapperMethodStatementsGenerator = new InjectMapperMethodStatementsGenerator();
        $this->identifierHashGenerator = new IdentifierHashGenerator();

        $this->disableGeneratedMapper = !$configuration->autoRegister;
    }

    /**
     * Generate Class AST given metadata for a mapper.
     *
     * @return Stmt[]
     *
     * @throws CompileException
     * @throws InvalidMappingException
     */
    public function generate(GeneratorMetadata $metadata): array
    {
        if ($this->disableGeneratedMapper) {
            throw new InvalidMappingException('No mapper found for source ' . $metadata->mapperMetadata->source . ' and target ' . $metadata->mapperMetadata->target);
        }

        $statements = [];
        if ($metadata->strictTypes) {
            $statements[] = new Stmt\Declare_([create_declare_item('strict_types', create_scalar_int(1))]);
        }

        [$constructorStatements, $duplicatedStatements, $setterStatements] = $this->mapMethodStatementsGenerator->getMappingStatements($metadata);

        $builder = (new Builder\Class_($metadata->mapperMetadata->className))
            ->makeFinal()
            ->extend(GeneratedMapper::class)
            ->addStmt($this->constructorMethod($metadata))
            ->addStmt($this->mapMethod($metadata, $duplicatedStatements, \count($constructorStatements) > 0))
        ;

        if (\count($constructorStatements) > 0) {
            $builder = $builder->addStmt($this->doConstructMethod($metadata, $constructorStatements));
        }

        $builder
            ->addStmt($this->doMapMethod($metadata, $setterStatements))
            ->addStmt($this->registerMappersMethod($metadata));

        if ($sourceHashMethod = $this->identifierHashGenerator->getSourceHashMethod($metadata)) {
            $builder->addStmt($sourceHashMethod);
        }

        if ($targetHashMethod = $this->identifierHashGenerator->getTargetHashMethod($metadata)) {
            $builder->addStmt($targetHashMethod);
        }

        if ($targetIdentifierMethod = $this->identifierHashGenerator->getTargetIdentifiersMethod($metadata)) {
            $builder->addStmt($targetIdentifierMethod);
        }

        $statements[] = $builder->getNode();

        return $statements;
    }

    /**
     * Create the constructor for this mapper.
     *
     * ```php
     * public function __construct() {
     *    // construct statements
     *    $this->extractCallbacks['propertyName'] = \Closure::bind(function ($object) {
     *       return $object->propertyName;
     *    };
     *    ...
     * }
     * ```
     */
    private function constructorMethod(GeneratorMetadata $metadata): Stmt\ClassMethod
    {
        return (new Builder\Method('initialize'))
            ->makePublic()
            ->setReturnType('void')
            ->addStmts($this->mapperConstructorGenerator->getStatements($metadata))
            ->getNode();
    }

    /**
     * Create the map method for this mapper.
     *
     * ```php
     * public function map($source, array $context = []) {
     *   ... // statements
     * }
     * ```
     *
     * @param list<Stmt> $duplicatedStatements
     */
    private function mapMethod(GeneratorMetadata $metadata, array $duplicatedStatements, bool $callDoConstruct): Stmt\ClassMethod
    {
        return (new Builder\Method('map'))
            ->makePublic()
            ->setReturnType('mixed')
            ->makeReturnByRef()
            ->addParam(new Param($metadata->variableRegistry->getSourceInput()))
            ->addParam(new Param($metadata->variableRegistry->getContext(), default: new Expr\Array_(), type: new Name('array')))
            ->addStmts($this->mapMethodStatementsGenerator->getStatements($metadata, $duplicatedStatements, $callDoConstruct))
            ->setDocComment(
                \sprintf(
                    '/** @param %s $%s */',
                    $metadata->mapperMetadata->source === 'array' ? $metadata->mapperMetadata->source : '\\' . $metadata->mapperMetadata->source,
                    'value'
                )
            )
            ->getNode();
    }

    /**
     * Create the doConstruct method for this mapper.
     *
     * ```php
     * public function doConstruct($value, &$result, array $context = []): void
     *   ... // statements
     * }
     * ```
     *
     * @param list<Stmt> $constructorStatements
     */
    private function doConstructMethod(GeneratorMetadata $metadata, array $constructorStatements): Stmt\ClassMethod
    {
        return (new Builder\Method('doConstruct'))
            ->makePrivate()
            ->setReturnType('void')
            ->addParam(new Param($metadata->variableRegistry->getSourceInput()))
            ->addParam(new Param($metadata->variableRegistry->getResult()))
            ->addParam(new Param($metadata->variableRegistry->getContext(), default: new Expr\Array_(), type: new Name('array')))
            ->addStmts($constructorStatements)
            ->getNode();
    }

    /**
     * Create the doMap method for this mapper.
     *
     * ```php
     * private function doMap($value, &$result, array $context = []): void {
     *   ... // statements
     * }
     * ```
     *
     * @param list<Stmt> $setterStatements
     */
    private function doMapMethod(GeneratorMetadata $metadata, array $setterStatements): Stmt\ClassMethod
    {
        return (new Builder\Method('doMap'))
            ->makePrivate()
            ->setReturnType('void')
            ->addParam(new Param($metadata->variableRegistry->getSourceInput()))
            ->addParam(new Param($metadata->variableRegistry->getResult(), byRef: true))
            ->addParam(new Param($metadata->variableRegistry->getContext(), default: new Expr\Array_(), type: new Name('array')))
            ->addStmts($setterStatements)
            ->getNode();
    }

    /**
     * Create the registerMappers methods for this mapper.
     *
     * This is not done into the constructor in order to avoid circular dependency between mappers
     *
     * ```php
     * public function registerMappers(AutoMapperRegistryInterface $autoMapperRegistry) {
     *   // inject mapper statements
     *   $this->mappers['SOURCE_TO_TARGET_MAPPER'] = $autoMapperRegistry->getMapper($source, $target);
     *   ...
     * }
     * ```
     */
    private function registerMappersMethod(GeneratorMetadata $metadata): Stmt\ClassMethod
    {
        return (new Builder\Method('registerMappers'))
            ->makePublic()
            ->setReturnType('void')
            ->addParam(new Param(
                var: $param = new Expr\Variable('autoMapperRegistry'),
                type: new Name(AutoMapperRegistryInterface::class))
            )
            ->addStmts($this->injectMapperMethodStatementsGenerator->getStatements($param, $metadata))
            ->getNode();
    }
}
