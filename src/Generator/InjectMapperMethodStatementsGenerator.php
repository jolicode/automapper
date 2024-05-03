<?php

declare(strict_types=1);

namespace AutoMapper\Generator;

use AutoMapper\Metadata\Dependency;
use AutoMapper\Metadata\GeneratorMetadata;
use PhpParser\Node\Arg;
use PhpParser\Node\Expr;
use PhpParser\Node\Scalar;
use PhpParser\Node\Stmt;

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
 *
 * @internal
 */
final readonly class InjectMapperMethodStatementsGenerator
{
    public function __construct()
    {
    }

    /**
     * @return list<Stmt>
     */
    public function getStatements(Expr\Variable $automapperRegistryVariable, GeneratorMetadata $metadata): array
    {
        $injectMapperStatements = [];

        /** @var Dependency $dependency */
        foreach ($metadata->getDependencies() as $dependency) {
            /*
             * If the transformer has dependencies, we inject the mappers for the dependencies
             * This allows to inject mappers when creating the service instead of resolving them at runtime which is faster
             *
             * $this->mappers[$dependency->name] = $autoMapperRegistry->getMapper($dependency->source, $dependency->target);
             */
            $injectMapperStatements[] = new Stmt\Expression(
                new Expr\Assign(
                    new Expr\ArrayDimFetch(
                        new Expr\PropertyFetch(new Expr\Variable('this'), 'mappers'),
                        new Scalar\String_($dependency->mapperDependency->name)
                    ),
                    new Expr\MethodCall($automapperRegistryVariable, 'getMapper', [
                        new Arg(new Scalar\String_($dependency->mapperDependency->source)),
                        new Arg(new Scalar\String_($dependency->mapperDependency->target)),
                    ])
                )
            );
        }

        return [
            ...$injectMapperStatements,
        ];
    }
}
