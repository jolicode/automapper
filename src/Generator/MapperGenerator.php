<?php

declare(strict_types=1);

namespace AutoMapper\Generator;

use AutoMapper\AutoMapperRegistryInterface;
use AutoMapper\Configuration;
use AutoMapper\Exception\CompileException;
use AutoMapper\Exception\NoMappingFoundException;
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
            $configuration->allowReadOnlyTargetToPopulate,
        );

        $this->injectMapperMethodStatementsGenerator = new InjectMapperMethodStatementsGenerator();

        $this->disableGeneratedMapper = !$configuration->autoRegister;
    }

    /**
     * Generate Class AST given metadata for a mapper.
     *
     * @throws CompileException
     * @throws NoMappingFoundException
     */
    public function generate(GeneratorMetadata $metadata): Stmt\Class_
    {
        if ($this->disableGeneratedMapper) {
            throw new NoMappingFoundException('No mapper found for source ' . $metadata->mapperMetadata->source . ' and target ' . $metadata->mapperMetadata->target);
        }

        return (new Builder\Class_($metadata->mapperMetadata->className))
            ->makeFinal()
            ->extend(GeneratedMapper::class)
            ->addStmt($this->constructorMethod($metadata))
            ->addStmt($this->mapMethod($metadata))
            ->addStmt($this->registerMappersMethod($metadata))
            ->getNode();
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
     */
    private function mapMethod(GeneratorMetadata $metadata): Stmt\ClassMethod
    {
        return (new Builder\Method('map'))
            ->makePublic()
            ->setReturnType('mixed')
            ->makeReturnByRef()
            ->addParam(new Param($metadata->variableRegistry->getSourceInput()))
            ->addParam(new Param($metadata->variableRegistry->getContext(), default: new Expr\Array_(), type: new Name('array')))
            ->addStmts($this->mapMethodStatementsGenerator->getStatements($metadata))
            ->setDocComment(
                sprintf(
                    '/** @param %s $%s */',
                    $metadata->mapperMetadata->source === 'array' ? $metadata->mapperMetadata->source : '\\' . $metadata->mapperMetadata->source,
                    'value'
                )
            )
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
