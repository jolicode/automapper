<?php

declare(strict_types=1);

namespace AutoMapper\Generator;

use AutoMapper\Metadata\GeneratorMetadata;
use AutoMapper\Metadata\PropertyMetadata;
use AutoMapper\Transformer\AllowNullValueTransformerInterface;
use AutoMapper\Transformer\ArrayTransformer;
use AutoMapper\Transformer\LazyCollectionTransformer;
use AutoMapper\Transformer\MapperDependency;
use AutoMapper\Transformer\ObjectTransformer;
use AutoMapper\Transformer\TransformerInterface;
use PhpParser\Node\Arg;
use PhpParser\Node\Expr;
use PhpParser\Node\Name;
use PhpParser\Node\Scalar;
use PhpParser\Node\Stmt;

/**
 * The `json` counterpart of {@see PropertyStatementsGenerator}: instead of assigning the
 * transformed value to the target, it yields the property's JSON, chunk by chunk, straight from
 * the source object.
 *
 * It reuses the mapper's read accessors and transformers (so renames, `#[MapTo]`, date/enum
 * handling, ... all still apply). For leaf values it `json_encode()`s the transformed value; for
 * a nested object or a list of objects it `yield from`s the nested object → json sub-mapper's own
 * `map()`, so a large nested collection streams element by element instead of being materialized
 * and encoded whole.
 *
 * @internal
 */
final readonly class JsonPropertyStatementsGenerator
{
    public function __construct(
        private PropertyConditionsGenerator $propertyConditionsGenerator,
    ) {
    }

    /**
     * The variable holding the `,` separator between properties, shared with the framing emitted
     * by the mapper's map() method.
     */
    public static function separatorVariable(): Expr\Variable
    {
        return new Expr\Variable('sep');
    }

    /**
     * @return Stmt[]
     */
    public function generate(GeneratorMetadata $metadata, PropertyMetadata $propertyMetadata): array
    {
        if ($propertyMetadata->ignored) {
            return [];
        }

        $variableRegistry = $metadata->variableRegistry;
        $fieldValueExpr = $propertyMetadata->source->accessor?->getExpression($variableRegistry->getSourceInput());

        if (null === $fieldValueExpr) {
            if (!$propertyMetadata->transformer instanceof AllowNullValueTransformerInterface) {
                return [];
            }

            $fieldValueExpr = new Expr\ConstFetch(new Name('null'));
        }

        $keyPrefix = new Expr\BinaryOp\Concat(
            self::separatorVariable(),
            new Scalar\String_('"' . $this->escapeKey($propertyMetadata->target->property) . '":'),
        );

        $propStatements = $this->propertyStatements($metadata, $propertyMetadata, $fieldValueExpr, $keyPrefix);

        $condition = $this->propertyConditionsGenerator->generate($metadata, $propertyMetadata);

        if ($condition) {
            return [new Stmt\If_($condition, ['stmts' => $propStatements])];
        }

        return $propStatements;
    }

    /**
     * The nested object → json dependencies referenced by this mapper, so they can be injected.
     *
     * @return MapperDependency[]
     */
    public function jsonDependencies(GeneratorMetadata $metadata): array
    {
        $dependencies = [];

        foreach ($metadata->propertiesMetadata as $propertyMetadata) {
            if ($propertyMetadata->ignored) {
                continue;
            }

            $transformer = $propertyMetadata->transformer;
            $objectTransformer = $transformer instanceof ObjectTransformer ? $transformer : $this->objectListItemTransformer($transformer);

            if ($objectTransformer === null || ($source = $this->streamableSource($objectTransformer)) === null) {
                continue;
            }

            $dependencies[$source] = new MapperDependency($this->jsonDependencyName($source), $source, 'json');
        }

        return array_values($dependencies);
    }

    /**
     * @return Stmt[]
     */
    private function propertyStatements(
        GeneratorMetadata $metadata,
        PropertyMetadata $propertyMetadata,
        Expr $fieldValueExpr,
        Expr $keyPrefix,
    ): array {
        $transformer = $propertyMetadata->transformer;
        $variableRegistry = $metadata->variableRegistry;
        $advanceSep = new Stmt\Expression(new Expr\Assign(self::separatorVariable(), new Scalar\String_(',')));

        // A nested object mapped by a sub-mapper: stream it via the nested object → json
        // sub-mapper's own map() so nested collections stream too.
        if ($transformer instanceof ObjectTransformer && ($source = $this->streamableSource($transformer)) !== null) {
            $valueVar = new Expr\Variable($variableRegistry->getUniqueVariableScope()->getUniqueName('jsonValue'));

            return [
                new Stmt\Expression(new Expr\Assign($valueVar, $fieldValueExpr)),
                new Stmt\Expression(new Expr\Yield_($keyPrefix)),
                $advanceSep,
                new Stmt\If_(new Expr\BinaryOp\Identical(new Expr\ConstFetch(new Name('null')), $valueVar), [
                    'stmts' => [new Stmt\Expression(new Expr\Yield_(new Scalar\String_('null')))],
                    'else' => new Stmt\Else_([$this->yieldFromMapper($source, $valueVar, $variableRegistry)]),
                ]),
            ];
        }

        // A list of objects mapped by a sub-mapper: stream `[` + each element + `]`.
        if (($itemTransformer = $this->objectListItemTransformer($transformer)) !== null
            && ($source = $this->streamableSource($itemTransformer)) !== null) {
            $scope = $variableRegistry->getUniqueVariableScope();
            $itemVar = new Expr\Variable($scope->getUniqueName('jsonItem'));
            $itemSepVar = new Expr\Variable($scope->getUniqueName('jsonItemSep'));

            return [
                new Stmt\Expression(new Expr\Yield_($keyPrefix)),
                $advanceSep,
                new Stmt\Expression(new Expr\Yield_(new Scalar\String_('['))),
                new Stmt\Expression(new Expr\Assign($itemSepVar, new Scalar\String_(''))),
                new Stmt\Foreach_(new Expr\BinaryOp\Coalesce($fieldValueExpr, new Expr\Array_()), $itemVar, [
                    'stmts' => [
                        new Stmt\Expression(new Expr\Yield_($itemSepVar)),
                        new Stmt\Expression(new Expr\Assign($itemSepVar, new Scalar\String_(','))),
                        new Stmt\If_(new Expr\BinaryOp\Identical(new Expr\ConstFetch(new Name('null')), $itemVar), [
                            'stmts' => [new Stmt\Expression(new Expr\Yield_(new Scalar\String_('null')))],
                            'else' => new Stmt\Else_([$this->yieldFromMapper($source, $itemVar, $variableRegistry)]),
                        ]),
                    ],
                ]),
                new Stmt\Expression(new Expr\Yield_(new Scalar\String_(']'))),
            ];
        }

        // Leaf value (scalar, date, enum, scalar list, dict, ...): transform then json_encode
        // the result in one native call.
        [$output, $propStatements] = $transformer->transform(
            $fieldValueExpr,
            $variableRegistry->getResult(),
            $propertyMetadata,
            $variableRegistry->getUniqueVariableScope(),
            $variableRegistry->getSourceInput(),
        );

        $propStatements[] = new Stmt\Expression(new Expr\Yield_(new Expr\BinaryOp\Concat(
            $keyPrefix,
            new Expr\FuncCall(new Name('json_encode'), [new Arg($output)]),
        )));
        $propStatements[] = new Stmt\Expression(new Expr\Assign(self::separatorVariable(), new Scalar\String_(',')));

        return $propStatements;
    }

    private function yieldFromMapper(string $source, Expr $value, VariableRegistry $variableRegistry): Stmt
    {
        return new Stmt\Expression(new Expr\YieldFrom(new Expr\MethodCall(
            new Expr\ArrayDimFetch(
                new Expr\PropertyFetch(new Expr\Variable('this'), 'mappers'),
                new Scalar\String_($this->jsonDependencyName($source)),
            ),
            'map',
            [
                new Arg($value),
                new Arg($variableRegistry->getContext()),
            ],
        )));
    }

    /**
     * The per-item {@see ObjectTransformer} when the property is a *list* of objects
     * (bare or lazy-collection wrapped), or null otherwise.
     */
    private function objectListItemTransformer(TransformerInterface $transformer): ?ObjectTransformer
    {
        if ($transformer instanceof ArrayTransformer) {
            $itemTransformer = $transformer->getItemTransformer();
        } elseif ($transformer instanceof LazyCollectionTransformer && $transformer->getEagerTransformer() instanceof ArrayTransformer) {
            $itemTransformer = $transformer->getItemTransformer();
        } else {
            return null;
        }

        return $itemTransformer instanceof ObjectTransformer ? $itemTransformer : null;
    }

    /**
     * The source class of the nested object mapping when it is an object → array mapping (so a
     * json sibling mapper exists for it), or null otherwise.
     *
     * @return class-string|null
     */
    private function streamableSource(ObjectTransformer $transformer): ?string
    {
        foreach ($transformer->getDependencies() as $dependency) {
            if ('array' !== $dependency->source && 'array' === $dependency->target) {
                /** @var class-string */
                return $dependency->source;
            }
        }

        return null;
    }

    private function jsonDependencyName(string $source): string
    {
        return 'Mapper_' . $source . '_json';
    }

    private function escapeKey(string $key): string
    {
        // Keys are property names; encode to be safe and strip the surrounding quotes.
        return substr(json_encode($key, JSON_THROW_ON_ERROR), 1, -1);
    }
}
