<?php

declare(strict_types=1);

namespace AutoMapper\Generator;

use AutoMapper\AutoMapperRegistryInterface;
use AutoMapper\Exception\CompileException;
use AutoMapper\Exception\ReadOnlyTargetException;
use AutoMapper\Extractor\ClassMethodToCallbackExtractor;
use AutoMapper\Extractor\WriteMutator;
use AutoMapper\GeneratedMapper;
use AutoMapper\MapperContext;
use AutoMapper\MapperGeneratorMetadataInterface;
use AutoMapper\Transformer\AssignedByReferenceTransformerInterface;
use AutoMapper\Transformer\DependentTransformerInterface;
use AutoMapper\Transformer\TransformerInterface;
use PhpParser\Node\Arg;
use PhpParser\Node\Expr;
use PhpParser\Node\Name;
use PhpParser\Node\Param;
use PhpParser\Node\Scalar;
use PhpParser\Node\Stmt;
use PhpParser\Parser;
use PhpParser\ParserFactory;
use Symfony\Component\Serializer\Mapping\ClassDiscriminatorResolverInterface;

/**
 * Generates code for a mapping class.
 *
 * @author Joel Wurtz <jwurtz@jolicode.com>
 */
final readonly class Generator
{
    private Parser $parser;

    public function __construct(
        private ClassMethodToCallbackExtractor $classMethodToCallbackExtractor,
        ?Parser $parser = null,
        private ?ClassDiscriminatorResolverInterface $classDiscriminator = null,
        private bool $allowReadOnlyTargetToPopulate = false,
    ) {
        $this->parser = $parser ?? (new ParserFactory())->create(ParserFactory::PREFER_PHP7);
    }

    /**
     * Generate Class AST given metadata for a mapper.
     *
     * @throws CompileException
     */
    public function generate(MapperGeneratorMetadataInterface $mapperGeneratorMetadata): Stmt\Class_
    {
        $propertiesMapping = $mapperGeneratorMetadata->getPropertiesMapping();

        $uniqueVariableScope = new UniqueVariableScope();
        $sourceInput = new Expr\Variable($uniqueVariableScope->getUniqueName('value'));
        $result = new Expr\Variable($uniqueVariableScope->getUniqueName('result'));
        $hashVariable = new Expr\Variable($uniqueVariableScope->getUniqueName('sourceHash'));
        $contextVariable = new Expr\Variable($uniqueVariableScope->getUniqueName('context'));
        $constructStatements = [];
        $addedDependencies = [];
        $canHaveCircularDependency = $mapperGeneratorMetadata->canHaveCircularReference() && 'array' !== $mapperGeneratorMetadata->getSource();

        /**
         * First statement is to check if the source is null, if so, return null.
         *
         * if (null === $source) {
         *    return $source;
         * ]
         */
        $statements = [
            new Stmt\If_(new Expr\BinaryOp\Identical(new Expr\ConstFetch(new Name('null')), $sourceInput), [
                'stmts' => [new Stmt\Return_($sourceInput)],
            ]),
        ];

        if ($canHaveCircularDependency) {
            /*
             * When there can be circular dependency in the mapping, the following statements try to use the reference for the source if it's available
             *
             * $sourceHash = spl_object_hash($source) . $target;
             * if (MapperContext::shouldHandleCircularReference($context, $sourceHash, $source)) {
             *     return MapperContext::handleCircularReference($context, $sourceHash, $source, $this->circularReferenceLimit, $this->circularReferenceHandler);
             * }
             */
            $statements[] = new Stmt\Expression(new Expr\Assign($hashVariable, new Expr\BinaryOp\Concat(new Expr\FuncCall(new Name('spl_object_hash'), [
                new Arg($sourceInput),
            ]),
                new Scalar\String_($mapperGeneratorMetadata->getTarget())
            )));
            $statements[] = new Stmt\If_(new Expr\StaticCall(new Name\FullyQualified(MapperContext::class), 'shouldHandleCircularReference', [
                new Arg($contextVariable),
                new Arg($hashVariable),
                new Arg(new Expr\PropertyFetch(new Expr\Variable('this'), 'circularReferenceLimit')),
            ]), [
                'stmts' => [
                    new Stmt\Return_(new Expr\StaticCall(new Name\FullyQualified(MapperContext::class), 'handleCircularReference', [
                        new Arg($contextVariable),
                        new Arg($hashVariable),
                        new Arg($sourceInput),
                        new Arg(new Expr\PropertyFetch(new Expr\Variable('this'), 'circularReferenceLimit')),
                        new Arg(new Expr\PropertyFetch(new Expr\Variable('this'), 'circularReferenceHandler')),
                    ])),
                ],
            ]);
        }

        /**
         * Get statements about how to create the object.
         *
         * $createObjectStmts : Statements to create the object
         * $inConstructor : Field to set in the constructor, this allow to transform them before the constructor is called
         * $constructStatementsForCreateObjects : Additional statements to add in the constructor
         * $injectMapperStatements : Additional statements to add in the injectMappers method, this allow to inject mappers for dependencies
         */
        [$createObjectStmts, $inConstructor, $constructStatementsForCreateObjects, $injectMapperStatements] = $this->getCreateObjectStatements($mapperGeneratorMetadata, $result, $contextVariable, $sourceInput, $uniqueVariableScope);
        $constructStatements = array_merge($constructStatements, $constructStatementsForCreateObjects);

        $targetToPopulate = new Expr\ArrayDimFetch($contextVariable, new Scalar\String_(MapperContext::TARGET_TO_POPULATE));

        /*
         * Get result from context if available, otherwise set it to null
         *
         * $result = $context[MapperContext::TARGET_TO_POPULATE] ?? null;
         */
        $statements[] = new Stmt\Expression(new Expr\Assign($result, new Expr\BinaryOp\Coalesce(
            $targetToPopulate,
            new Expr\ConstFetch(new Name('null'))
        )));
        if (!$this->allowReadOnlyTargetToPopulate && $mapperGeneratorMetadata->isTargetReadOnlyClass()) {
            /*
             * If the target is a read-only class, we throw an exception if the target is not null
             *
             * if ($contextVariable[MapperContext::ALLOW_READONLY_TARGET_TO_POPULATE] ?? false && is_object($targetToPopulate)) {
             *     throw new ReadOnlyTargetException();
             * }
             */
            $statements[] = new Stmt\If_(
                new Expr\BinaryOp\BooleanAnd(
                    new Expr\BooleanNot(new Expr\BinaryOp\Coalesce(new Expr\ArrayDimFetch($contextVariable, new Scalar\String_(MapperContext::ALLOW_READONLY_TARGET_TO_POPULATE)), new Expr\ConstFetch(new Name('false')))),
                    new Expr\FuncCall(new Name('is_object'), [new Arg(new Expr\BinaryOp\Coalesce($targetToPopulate, new Expr\ConstFetch(new Name('null'))))])
                ), [
                'stmts' => [new Stmt\Expression(new Expr\Throw_(new Expr\New_(new Name(ReadOnlyTargetException::class))))],
            ]);
        }

        /*
         * If the result is null, we create the object
         *
         * if (null === $result) {
         *    ... // create object statements @see getCreateObjectStatements
         * }
         */
        $statements[] = new Stmt\If_(new Expr\BinaryOp\Identical(new Expr\ConstFetch(new Name('null')), $result), [
            'stmts' => $createObjectStmts,
        ]);

        foreach ($propertiesMapping as $propertyMapping) {
            if (!$propertyMapping->transformer instanceof DependentTransformerInterface) {
                continue;
            }

            foreach ($propertyMapping->transformer->getDependencies() as $dependency) {
                if (isset($addedDependencies[$dependency->name])) {
                    continue;
                }

                /*
                 * If the transformer has dependencies, we inject the mappers for the dependencies
                 * This allows to inject mappers when creating the service instead of resolving them at runtime which is faster
                 *
                 * $this->mappers[$dependency->name] = $autoMapperRegistry->getMapper($dependency->source, $dependency->target);
                 */
                $injectMapperStatements[] = new Stmt\Expression(new Expr\Assign(
                    new Expr\ArrayDimFetch(new Expr\PropertyFetch(new Expr\Variable('this'), 'mappers'), new Scalar\String_($dependency->name)),
                    new Expr\MethodCall(new Expr\Variable('autoMapperRegistry'), 'getMapper', [
                        new Arg(new Scalar\String_($dependency->source)),
                        new Arg(new Scalar\String_($dependency->target)),
                    ])
                ));
                $addedDependencies[$dependency->name] = true;
            }
        }

        $addedDependenciesStatements = [];
        if ($addedDependencies) {
            if ($canHaveCircularDependency) {
                /*
                 * Here we register the result into the context to allow circular dependency, it's done before mapping so if there is a circular dependency, it will be correctly handled
                 *
                 * $context = MapperContext::withReference($context, $sourceHash, $result);
                 */
                $addedDependenciesStatements[] = new Stmt\Expression(new Expr\Assign(
                    $contextVariable,
                    new Expr\StaticCall(new Name\FullyQualified(MapperContext::class), 'withReference', [
                        new Arg($contextVariable),
                        new Arg($hashVariable),
                        new Arg($result),
                    ])
                ));
            }

            /*
             * We increase the depth of the context to allow to check the max depth of the mapping
             *
             * $context = MapperContext::withIncrementedDepth($context);
             */
            $addedDependenciesStatements[] = new Stmt\Expression(new Expr\Assign(
                $contextVariable,
                new Expr\StaticCall(new Name\FullyQualified(MapperContext::class), 'withIncrementedDepth', [
                    new Arg($contextVariable),
                ])
            ));
        }

        $duplicatedStatements = [];
        $setterStatements = [];
        foreach ($propertiesMapping as $propertyMapping) {
            /*
             * This is the main loop to map the properties from the source to the target, there is 3 main steps in order to generated this code :
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
             * $this->hydrateCallbacks['propertyName']($target, $this->mappers['SOURCE_TO_TARGET_MAPPER']->map($this->extractCallbacks['propertyName']($source), $context));
             */
            if ($propertyMapping->shouldIgnoreProperty($mapperGeneratorMetadata->shouldMapPrivateProperties())) {
                continue;
            }

            $fieldValueVariable = new Expr\Variable($uniqueVariableScope->getUniqueName('fieldValue'));
            /** Create expression on how to read the value from the source */
            $sourcePropertyAccessor = new Expr\Assign($fieldValueVariable, $propertyMapping->readAccessor->getExpression($sourceInput));

            $transformer = $propertyMapping->transformer;

            if (\is_string($transformer)) {
                /* If the transformer is a string, it means it's a custom transformer, so we extract the code of the transform method and wrap it into a closure */
                $output = $this->classMethodToCallbackExtractor->extract($transformer, 'transform', [new Arg($fieldValueVariable)]);
                $propStatements = [];
            } else {
                /* Create expression to transform the read value into the wanted written value, depending on the transform it may add new statements to get the correct value */
                [$output, $propStatements] = $transformer->transform($fieldValueVariable, $result, $propertyMapping, $uniqueVariableScope);
            }

            $extractCallback = $propertyMapping->readAccessor->getExtractCallback($mapperGeneratorMetadata->getSource());

            if (null !== $extractCallback) {
                /*
                 * Add read callback to the constructor of the generated mapper
                 *
                 * $this->extractCallbacks['propertyName'] = $extractCallback;
                 */
                $constructStatements[] = new Stmt\Expression(new Expr\Assign(
                    new Expr\ArrayDimFetch(new Expr\PropertyFetch(new Expr\Variable('this'), 'extractCallbacks'), new Scalar\String_($propertyMapping->property)),
                    $extractCallback
                ));
            }

            if (null === $propertyMapping->writeMutator) {
                continue;
            }

            if ($propertyMapping->writeMutator->type !== WriteMutator::TYPE_ADDER_AND_REMOVER) {
                /** Create expression to write the transformed value to the target only if not add / remove mutator, as it's already called by the transformer in this case */
                $writeExpression = $propertyMapping->writeMutator->getExpression($result, $output, $transformer instanceof AssignedByReferenceTransformerInterface ? $transformer->assignByRef() : false);
                if (null === $writeExpression) {
                    continue;
                }

                $propStatements[] = new Stmt\Expression($writeExpression);
            }

            $hydrateCallback = $propertyMapping->writeMutator->getHydrateCallback($mapperGeneratorMetadata->getTarget());

            if (null !== $hydrateCallback) {
                /*
                 * Add hydrate callback to the constructor of the generated mapper
                 *
                 * $this->hydrateCallback['propertyName'] = $hydrateCallback;
                 */
                $constructStatements[] = new Stmt\Expression(new Expr\Assign(
                    new Expr\ArrayDimFetch(new Expr\PropertyFetch(new Expr\Variable('this'), 'hydrateCallbacks'), new Scalar\String_($propertyMapping->property)),
                    $hydrateCallback
                ));
            }

            /** We generate a list of conditions that will allow the field to be mapped to the target */
            $conditions = [];

            if ($propertyMapping->checkExists) {
                if (\stdClass::class === $mapperGeneratorMetadata->getSource()) {
                    /*
                     * In case of source is an \stdClass we ensure that the property exists
                     * property_exists($source, 'propertyName')
                     */
                    $conditions[] = new Expr\FuncCall(new Name('property_exists'), [
                        new Arg($sourceInput),
                        new Arg(new Scalar\String_($propertyMapping->property)),
                    ]);
                }

                if ('array' === $mapperGeneratorMetadata->getSource()) {
                    /*
                     * In case of source is an array we ensure that the key exists
                     * array_key_exists('propertyName', $source)
                     */
                    $conditions[] = new Expr\FuncCall(new Name('array_key_exists'), [
                        new Arg(new Scalar\String_($propertyMapping->property)),
                        new Arg($sourceInput),
                    ]);
                }
            }

            if ($mapperGeneratorMetadata->shouldCheckAttributes()) {
                /*
                 * In case of supporting attributes checking, we check if the property is allowed to be mapped
                 * MapperContext::isAllowedAttribute($context, 'propertyName', $source)
                 */
                $conditions[] = new Expr\StaticCall(new Name\FullyQualified(MapperContext::class), 'isAllowedAttribute', [
                    new Arg($contextVariable),
                    new Arg(new Scalar\String_($propertyMapping->property)),
                    new Arg($sourcePropertyAccessor),
                ]);
            }

            if (null !== $propertyMapping->sourceGroups) {
                /*
                 * When there is groups associated to the source property we check if the context has the same groups
                 *
                 * (null !== $context[MapperContext::GROUPS] ?? null && array_intersect($context[MapperContext::GROUPS] ?? [], ['group1', 'group2']))
                 */
                $conditions[] = new Expr\BinaryOp\BooleanAnd(
                    new Expr\BinaryOp\NotIdentical(
                        new Expr\ConstFetch(new Name('null')),
                        new Expr\BinaryOp\Coalesce(
                            new Expr\ArrayDimFetch($contextVariable, new Scalar\String_(MapperContext::GROUPS)),
                            new Expr\Array_()
                        )
                    ),
                    new Expr\FuncCall(new Name('array_intersect'), [
                        new Arg(new Expr\BinaryOp\Coalesce(
                            new Expr\ArrayDimFetch($contextVariable, new Scalar\String_(MapperContext::GROUPS)),
                            new Expr\Array_()
                        )),
                        new Arg(new Expr\Array_(array_map(function (string $group) {
                            return new Expr\ArrayItem(new Scalar\String_($group));
                        }, $propertyMapping->sourceGroups))),
                    ])
                );
            }

            if (null !== $propertyMapping->targetGroups) {
                /*
                 * When there is groups associated to the target property we check if the context has the same groups
                 *
                 * (null !== $context[MapperContext::GROUPS] ?? null && array_intersect($context[MapperContext::GROUPS] ?? [], ['group1', 'group2']))
                 */
                $conditions[] = new Expr\BinaryOp\BooleanAnd(
                    new Expr\BinaryOp\NotIdentical(
                        new Expr\ConstFetch(new Name('null')),
                        new Expr\BinaryOp\Coalesce(
                            new Expr\ArrayDimFetch($contextVariable, new Scalar\String_(MapperContext::GROUPS)),
                            new Expr\Array_()
                        )
                    ),
                    new Expr\FuncCall(new Name('array_intersect'), [
                        new Arg(new Expr\BinaryOp\Coalesce(
                            new Expr\ArrayDimFetch($contextVariable, new Scalar\String_(MapperContext::GROUPS)),
                            new Expr\Array_()
                        )),
                        new Arg(new Expr\Array_(array_map(function (string $group) {
                            return new Expr\ArrayItem(new Scalar\String_($group));
                        }, $propertyMapping->targetGroups))),
                    ])
                );
            }

            if (null !== $propertyMapping->maxDepth) {
                /*
                 * When there is a max depth for this property we check if the context has a depth lower or equal to the max depth
                 *
                 * ($context[MapperContext::DEPTH] ?? 0) <= $maxDepth
                 */
                $conditions[] = new Expr\BinaryOp\SmallerOrEqual(
                    new Expr\BinaryOp\Coalesce(
                        new Expr\ArrayDimFetch($contextVariable, new Scalar\String_(MapperContext::DEPTH)),
                        new Expr\ConstFetch(new Name('0'))
                    ),
                    new Scalar\LNumber($propertyMapping->maxDepth)
                );
            }

            if ($conditions) {
                /*
                 * If there is any conditions generated we encapsulate the mapping into it.
                 *
                 * if (condition1 && condition2 && ...) {
                 *    ... // mapping statements
                 * }
                 */
                $condition = array_shift($conditions);

                while ($conditions) {
                    $condition = new Expr\BinaryOp\BooleanAnd($condition, array_shift($conditions));
                }

                $propStatements = [new Stmt\If_($condition, [
                    'stmts' => $propStatements,
                ])];
            }

            /*
             * Here we dispatch those statements into two categories
             *  * Statements that need to be executed before the constructor, if the property need to be written in the constructor
             *  * Statements that need to be executed after the constructor.
             */
            $propInConstructor = \in_array($propertyMapping->property, $inConstructor, true);
            foreach ($propStatements as $propStatement) {
                if ($propInConstructor) {
                    $duplicatedStatements[] = $propStatement;
                } else {
                    $setterStatements[] = $propStatement;
                }
            }
        }

        if (\count($duplicatedStatements) > 0 && \count($inConstructor)) {
            /*
             * Generate else statements when the result is already an object, which means it has already been created, so we need to execute the statements that need to be executed before the constructor since the constructor has already been called
             * if (null !== $result {
             *     .. // create object statements
             * } else {
             *     // remap property from the constructor in case object already exists so we do not loose information
             *     $source->propertyName = $this->extractCallbacks['propertyName']($source);
             *     ...
             * }
             */
            $statements[] = new Stmt\Else_(array_merge($addedDependenciesStatements, $duplicatedStatements));
        } else {
            foreach ($addedDependenciesStatements as $statement) {
                $statements[] = $statement;
            }
        }

        /* Add the rest of statements to handle the mapping */
        foreach ($setterStatements as $propStatement) {
            $statements[] = $propStatement;
        }

        /* return $result; */
        $statements[] = new Stmt\Return_($result);

        /*
         * Create the map method for this mapper.
         *
         * public function map($source, array $context = []) {
         *   ... // statements
         * }
         */
        $mapMethod = new Stmt\ClassMethod('map', [
            'flags' => Stmt\Class_::MODIFIER_PUBLIC,
            'params' => [
                new Param(new Expr\Variable($sourceInput->name)),
                new Param(new Expr\Variable('context'), new Expr\Array_(), 'array'),
            ],
            'byRef' => true,
            'stmts' => $statements,
            'returnType' => \PHP_VERSION_ID >= 80000 ? 'mixed' : null,
        ]);

        /*
         * Create the constructor for this mapper.
         *
         * public function __construct() {
         *    // construct statements
         *    $this->extractCallbacks['propertyName'] = \Closure::bind(function ($object) {
         *       return $object->propertyName;
         *    };
         *    ...
         * }
         */
        $constructMethod = new Stmt\ClassMethod('__construct', [
            'flags' => Stmt\Class_::MODIFIER_PUBLIC,
            'stmts' => $constructStatements,
        ]);

        $classStmts = [$constructMethod, $mapMethod];

        if (\count($injectMapperStatements) > 0) {
            /*
             * Create the injectMapper methods for this mapper
             *
             * This is not done into the constructor in order to avoid circular dependency between mappers
             *
             * public function injectMappers(AutoMapperRegistryInterface $autoMapperRegistry) {
             *   // inject mapper statements
             *   $this->mappers['SOURCE_TO_TARGET_MAPPER'] = $autoMapperRegistry->getMapper($source, $target);
             *   ...
             * }
             */
            $classStmts[] = new Stmt\ClassMethod('injectMappers', [
                'flags' => Stmt\Class_::MODIFIER_PUBLIC,
                'params' => [
                    new Param(new Expr\Variable('autoMapperRegistry'), null, new Name\FullyQualified(AutoMapperRegistryInterface::class)),
                ],
                'returnType' => 'void',
                'stmts' => $injectMapperStatements,
            ]);
        }

        /*
         * Create the class for this mapper
         *
         * final class SourceToTargetMapper extends GeneratedMapper {
         *     ... // class methods
         * }
         */
        return new Stmt\Class_($mapperGeneratorMetadata->getMapperClassName(), [
            'flags' => Stmt\Class_::MODIFIER_FINAL,
            'extends' => new Name\FullyQualified(GeneratedMapper::class),
            'stmts' => $classStmts,
        ]);
    }

    private function getCreateObjectStatements(MapperGeneratorMetadataInterface $mapperMetadata, Expr\Variable $result, Expr\Variable $contextVariable, Expr\Variable $sourceInput, UniqueVariableScope $uniqueVariableScope): array
    {
        $target = $mapperMetadata->getTarget();
        $source = $mapperMetadata->getSource();

        if ('array' === $target) {
            /*
             * If the target is an array, we just create an empty array.
             * $result = [];
             */
            return [[new Stmt\Expression(new Expr\Assign($result, new Expr\Array_()))], [], [], []];
        }

        if (\stdClass::class === $target && \stdClass::class === $source) {
            /*
             * If the target and source is a stdClass, we just clone the object using serialization
             * $result = unserialize(serialize($source));
             */
            return [[new Stmt\Expression(new Expr\Assign($result, new Expr\FuncCall(new Name('unserialize'), [new Arg(new Expr\FuncCall(new Name('serialize'), [new Arg($sourceInput)]))])))], [], [], []];
        }

        if (\stdClass::class === $target) {
            /*
             * If the target is a stdClass, we create a new stdClass
             * $result = \new stdClass();
             */
            return [[new Stmt\Expression(new Expr\Assign($result, new Expr\New_(new Name(\stdClass::class))))], [], [], []];
        }

        $reflectionClass = new \ReflectionClass($target);
        $targetConstructor = $reflectionClass->getConstructor();
        $createObjectStatements = [];
        $inConstructor = [];
        $constructStatements = [];
        $injectMapperStatements = [];
        $classDiscriminatorMapping = 'array' !== $target && null !== $this->classDiscriminator ? $this->classDiscriminator->getMappingForClass($target) : null;

        if (
            null !== $classDiscriminatorMapping
            && null !== ($propertyMapping = $mapperMetadata->getPropertyMapping($classDiscriminatorMapping->getTypeProperty()))
            && $propertyMapping->transformer instanceof TransformerInterface
        ) {
            /* Here we generated the code that allow to put the type into the output variable, so we are able to determine which mapper to use */
            [$output, $createObjectStatements] = $propertyMapping->transformer->transform($propertyMapping->readAccessor->getExpression($sourceInput), $result, $propertyMapping, $uniqueVariableScope);

            foreach ($classDiscriminatorMapping->getTypesMapping() as $typeValue => $typeTarget) {
                $mapperName = 'Discriminator_Mapper_' . $source . '_' . $typeTarget;

                /*
                 * We inject dependencies for all the discriminator variant
                 *
                 *  $this->mappers['Discriminator_Mapper_VariantA'] = $autoMapperRegistry->getMapper($source, VariantA::class);
                 *  $this->mappers['Discriminator_Mapper_VariantB'] = $autoMapperRegistry->getMapper($source, VariantB::class);
                 *  ...
                 */
                $injectMapperStatements[] = new Stmt\Expression(new Expr\Assign(
                    new Expr\ArrayDimFetch(new Expr\PropertyFetch(new Expr\Variable('this'), 'mappers'), new Scalar\String_($mapperName)),
                    new Expr\MethodCall(new Expr\Variable('autoMapperRegistry'), 'getMapper', [
                        new Arg(new Scalar\String_($source)),
                        new Arg(new Scalar\String_($typeTarget)),
                    ])
                ));

                /*
                 * We return the object created with the correct mapper depending on the variant, this will skip the next mapping phase in this situation
                 *
                 * if ('VariantA' === $output) {
                 *     return $this->mappers['Discriminator_Mapper_VariantA']->map($source, $context);
                 * }
                 */
                $createObjectStatements[] = new Stmt\If_(new Expr\BinaryOp\Identical(
                    new Scalar\String_($typeValue),
                    $output
                ), [
                    'stmts' => [
                        new Stmt\Return_(new Expr\MethodCall(new Expr\ArrayDimFetch(
                            new Expr\PropertyFetch(new Expr\Variable('this'), 'mappers'),
                            new Scalar\String_($mapperName)
                        ), 'map', [
                            new Arg($sourceInput),
                            new Expr\Variable('context'),
                        ])),
                    ],
                ]);
            }
        }

        $propertiesMapping = $mapperMetadata->getPropertiesMapping();

        if (null !== $targetConstructor && $mapperMetadata->hasConstructor()) {
            $constructArguments = [];

            foreach ($propertiesMapping as $propertyMapping) {
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
                if (null === $propertyMapping->writeMutatorConstructor || null === ($parameter = $propertyMapping->writeMutatorConstructor->parameter)) {
                    continue;
                }

                $constructVar = new Expr\Variable($uniqueVariableScope->getUniqueName('constructArg'));

                $fieldValueExpr = $propertyMapping->readAccessor->getExpression($sourceInput);

                if (\is_string($propertyMapping->transformer)) {
                    /* If the transformer is a string, it means it's a custom transformer, so we extract the code of the transform method and wrap it into a closure */
                    $propStatements = [];
                    $output = $this->classMethodToCallbackExtractor->extract($propertyMapping->transformer, 'transform', [new Arg($fieldValueExpr)]);
                } else {
                    /* Get extract and transform statements for this property */
                    [$output, $propStatements] = $propertyMapping->transformer->transform($fieldValueExpr, $constructVar, $propertyMapping, $uniqueVariableScope);
                }

                $constructArguments[$parameter->getPosition()] = new Arg($constructVar);

                /*
                 * Check if there is a constructor argument in the context, otherwise we use the transformed value
                 *
                 * if (MapperContext::hasConstructorArgument($context, $target, 'propertyName')) {
                 *    $constructArg1 = MapperContext::getConstructorArgument($context, $target, 'propertyName');
                 * } else {
                 *    $constructArg1 = $source->propertyName;
                 * }
                 */
                $propStatements[] = new Stmt\Expression(new Expr\Assign($constructVar, $output));
                $createObjectStatements[] = new Stmt\If_(new Expr\StaticCall(new Name\FullyQualified(MapperContext::class), 'hasConstructorArgument', [
                    new Arg($contextVariable),
                    new Arg(new Scalar\String_($target)),
                    new Arg(new Scalar\String_($propertyMapping->property)),
                ]), [
                    'stmts' => [
                        new Stmt\Expression(new Expr\Assign($constructVar, new Expr\StaticCall(new Name\FullyQualified(MapperContext::class), 'getConstructorArgument', [
                            new Arg($contextVariable),
                            new Arg(new Scalar\String_($target)),
                            new Arg(new Scalar\String_($propertyMapping->property)),
                        ]))),
                    ],
                    'else' => new Stmt\Else_($propStatements),
                ]);

                $inConstructor[] = $propertyMapping->property;
            }

            /* We loop to get constructor arguments that were not present in the source */
            foreach ($targetConstructor->getParameters() as $constructorParameter) {
                if (!\array_key_exists($constructorParameter->getPosition(), $constructArguments) && $constructorParameter->isDefaultValueAvailable()) {
                    $constructVar = new Expr\Variable($uniqueVariableScope->getUniqueName('constructArg'));

                    /*
                     * Check if there is a constructor argument in the context, otherwise we use the default value
                     *
                     * if (MapperContext::hasConstructorArgument($context, $target, 'propertyName')) {
                     *    $constructArg2 = MapperContext::getConstructorArgument($context, $target, 'propertyName');
                     * } else {
                     *    $constructArg2 = 'default value';
                     * }
                     */
                    $createObjectStatements[] = new Stmt\If_(new Expr\StaticCall(new Name\FullyQualified(MapperContext::class), 'hasConstructorArgument', [
                        new Arg($contextVariable),
                        new Arg(new Scalar\String_($target)),
                        new Arg(new Scalar\String_($constructorParameter->getName())),
                    ]), [
                        'stmts' => [
                            new Stmt\Expression(new Expr\Assign($constructVar, new Expr\StaticCall(new Name\FullyQualified(MapperContext::class), 'getConstructorArgument', [
                                new Arg($contextVariable),
                                new Arg(new Scalar\String_($target)),
                                new Arg(new Scalar\String_($constructorParameter->getName())),
                            ]))),
                        ],
                        'else' => new Stmt\Else_([
                            new Stmt\Expression(new Expr\Assign($constructVar, $this->getValueAsExpr($constructorParameter->getDefaultValue()))),
                        ]),
                    ]);

                    $constructArguments[$constructorParameter->getPosition()] = new Arg($constructVar);
                }
            }

            ksort($constructArguments);

            /*
             * Create object with the constructor arguments
             *
             * $result = new Foo($constructArg1, $constructArg2, ...);
             */
            $createObjectStatements[] = new Stmt\Expression(new Expr\Assign($result, new Expr\New_(new Name\FullyQualified($target), $constructArguments)));
        } elseif (null !== $targetConstructor && $mapperMetadata->isTargetCloneable()) {
            /*
             * When the target does not have a constructor but is cloneable, we clone a cached version of the target created with reflection to improve performance
             *
             * // constructor of mapper
             * $this->cachedTarget = (new \ReflectionClass(Foo:class))->newInstanceWithoutConstructor();
             *
             * // map method
             * $result = clone $this->cachedTarget;
             */
            $constructStatements[] = new Stmt\Expression(new Expr\Assign(
                new Expr\PropertyFetch(new Expr\Variable('this'), 'cachedTarget'),
                new Expr\MethodCall(new Expr\New_(new Name\FullyQualified(\ReflectionClass::class), [
                    new Arg(new Scalar\String_($target)),
                ]), 'newInstanceWithoutConstructor')
            ));
            $createObjectStatements[] = new Stmt\Expression(new Expr\Assign($result, new Expr\Clone_(new Expr\PropertyFetch(new Expr\Variable('this'), 'cachedTarget'))));
        } elseif (null !== $targetConstructor) {
            /*
             * When the target does not have a constructor and is not cloneable, we cache the reflection class to improve performance
             *
             * // constructor of mapper
             * $this->cachedTarget = (new \ReflectionClass(Foo:class));
             *
             * // map method
             * $result = $this->cachedTarget->newInstanceWithoutConstructor();
             */
            $constructStatements[] = new Stmt\Expression(new Expr\Assign(
                new Expr\PropertyFetch(new Expr\Variable('this'), 'cachedTarget'),
                new Expr\New_(new Name\FullyQualified(\ReflectionClass::class), [
                    new Arg(new Scalar\String_($target)),
                ])
            ));
            $createObjectStatements[] = new Stmt\Expression(new Expr\Assign($result, new Expr\MethodCall(
                new Expr\PropertyFetch(new Expr\Variable('this'), 'cachedTarget'),
                'newInstanceWithoutConstructor'
            )));
        } else {
            /*
             * Create object with constructor (which have no arguments)
             *
             * $result = new Foo();
             */
            $createObjectStatements[] = new Stmt\Expression(new Expr\Assign($result, new Expr\New_(new Name\FullyQualified($target))));
        }

        return [$createObjectStatements, $inConstructor, $constructStatements, $injectMapperStatements];
    }

    private function getValueAsExpr($value)
    {
        $expr = $this->parser->parse('<?php ' . var_export($value, true) . ';')[0];

        if ($expr instanceof Stmt\Expression) {
            return $expr->expr;
        }

        return $expr;
    }
}
