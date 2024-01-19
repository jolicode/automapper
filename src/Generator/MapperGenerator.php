<?php

declare(strict_types=1);

namespace AutoMapper\Generator;

use AutoMapper\AutoMapperRegistryInterface;
use AutoMapper\Exception\CompileException;
use AutoMapper\Extractor\CustomTransformerExtractor;
use AutoMapper\GeneratedMapper;
use AutoMapper\Generator\Shared\CachedReflectionStatementsGenerator;
use AutoMapper\Generator\Shared\ClassDiscriminatorResolver;
use AutoMapper\Generator\Shared\DiscriminatorStatementsGenerator;
use AutoMapper\MapperGeneratorMetadataInterface;
use PhpParser\Builder;
use PhpParser\Node\Expr;
use PhpParser\Node\Param;
use PhpParser\Node\Stmt;

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

    public function __construct(
        CustomTransformerExtractor $customTransformerExtractor,
        ClassDiscriminatorResolver $classDiscriminatorResolver,
        bool $allowReadOnlyTargetToPopulate = false,
    ) {
        $this->mapperConstructorGenerator = new MapperConstructorGenerator(
            $cachedReflectionStatementsGenerator = new CachedReflectionStatementsGenerator()
        );

        $this->mapMethodStatementsGenerator = new MapMethodStatementsGenerator(
            $discriminatorStatementsGenerator = new DiscriminatorStatementsGenerator($classDiscriminatorResolver),
            $cachedReflectionStatementsGenerator,
            $customTransformerExtractor,
            $allowReadOnlyTargetToPopulate,
        );

        $this->injectMapperMethodStatementsGenerator = new InjectMapperMethodStatementsGenerator(
            $discriminatorStatementsGenerator
        );
    }

    /**
     * Generate Class AST given metadata for a mapper.
     *
     * @throws CompileException
     */
    public function generate(MapperGeneratorMetadataInterface $mapperMetadata): Stmt\Class_
    {
        return (new Builder\Class_($mapperMetadata->getMapperClassName()))
            ->makeFinal()
            ->extend(GeneratedMapper::class)
            ->addStmt($this->constructorMethod($mapperMetadata))
            ->addStmt($this->mapMethod($mapperMetadata))
            ->addStmt($this->injectMappersMethod($mapperMetadata))
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
    private function constructorMethod(MapperGeneratorMetadataInterface $mapperMetadata): Stmt\ClassMethod
    {
        return (new Builder\Method('__construct'))
            ->makePublic()
            ->addStmts($this->mapperConstructorGenerator->getStatements($mapperMetadata))
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
    private function mapMethod(MapperGeneratorMetadataInterface $mapperMetadata): Stmt\ClassMethod
    {
        $variableRegistry = $mapperMetadata->getVariableRegistry();

        return (new Builder\Method('map'))
            ->makePublic()
            ->setReturnType('mixed')
            ->makeReturnByRef()
            ->addParam(new Param($variableRegistry->getSourceInput()))
            ->addParam(new Param($variableRegistry->getContext(), default: new Expr\Array_(), type: 'array'))
            ->addStmts($this->mapMethodStatementsGenerator->getStatements($mapperMetadata))
            ->setDocComment(
                sprintf(
                    '/** @param %s $%s */',
                    $mapperMetadata->getSource() === 'array' ? $mapperMetadata->getSource() : '\\' . $mapperMetadata->getSource(),
                    'value'
                )
            )
            ->getNode();
    }

    /**
     * Create the injectMapper methods for this mapper.
     *
     * This is not done into the constructor in order to avoid circular dependency between mappers
     *
     * ```php
     * public function injectMappers(AutoMapperRegistryInterface $autoMapperRegistry) {
     *   // inject mapper statements
     *   $this->mappers['SOURCE_TO_TARGET_MAPPER'] = $autoMapperRegistry->getMapper($source, $target);
     *   ...
     * }
     * ```
     */
    private function injectMappersMethod(MapperGeneratorMetadataInterface $mapperMetadata): Stmt\ClassMethod
    {
        return (new Builder\Method('injectMappers'))
            ->makePublic()
            ->setReturnType('void')
            ->addParam(
                new Param(
                    var: $param = new Expr\Variable('autoMapperRegistry'),
                    type: AutoMapperRegistryInterface::class
                )
            )
            ->addStmts($this->injectMapperMethodStatementsGenerator->getStatements($param, $mapperMetadata))
            ->getNode();
    }
}
