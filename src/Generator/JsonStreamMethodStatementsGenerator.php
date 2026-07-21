<?php

declare(strict_types=1);

namespace AutoMapper\Generator;

use AutoMapper\Metadata\GeneratorMetadata;
use AutoMapper\Metadata\PropertyMetadata;
use AutoMapper\Transformer\AllowNullValueTransformerInterface;
use AutoMapper\Transformer\ArrayTransformer;
use AutoMapper\Transformer\LazyCollectionTransformer;
use AutoMapper\Transformer\ObjectTransformer;
use AutoMapper\Transformer\TransformerInterface;
use PhpParser\Node\Arg;
use PhpParser\Node\Expr;
use PhpParser\Node\Name;
use PhpParser\Node\Scalar;
use PhpParser\Node\Stmt;

/**
 * Generates the body of a `mapToJsonStream()` generator method for an object → array
 * mapper: instead of building an array and returning it, it yields the JSON string
 * chunk by chunk, straight from the source object.
 *
 * It reuses the mapper's read accessors and transformers (so renames, `#[MapTo]`,
 * date/enum handling, ... all still apply). For leaf values it `json_encode()`s the
 * transformed value; for a nested object or a list of objects it `yield from`s the
 * sub-mapper's own `mapToJsonStream()` (found through the transformer's mapper
 * dependency), so a large nested collection is streamed element by element instead
 * of being materialized and encoded whole.
 *
 * @internal
 */
final readonly class JsonStreamMethodStatementsGenerator
{
    public function __construct(
        private PropertyConditionsGenerator $propertyConditionsGenerator,
    ) {
    }

    /**
     * @return Stmt[]
     */
    public function getStatements(GeneratorMetadata $metadata): array
    {
        $variableRegistry = $metadata->variableRegistry;
        $sepVar = new Expr\Variable('sep');

        $statements = [
            // A scratch array so transformers that reference the target's existing
            // value ($result[...]) keep working; it is never returned.
            new Stmt\Expression(new Expr\Assign($variableRegistry->getResult(), new Expr\Array_())),
            new Stmt\Expression(new Expr\Yield_(new Scalar\String_('{'))),
            new Stmt\Expression(new Expr\Assign($sepVar, new Scalar\String_(''))),
        ];

        foreach ($metadata->propertiesMetadata as $propertyMetadata) {
            if ($propertyMetadata->ignored) {
                continue;
            }

            $fieldValueExpr = $propertyMetadata->source->accessor?->getExpression($variableRegistry->getSourceInput());

            if (null === $fieldValueExpr) {
                if (!$propertyMetadata->transformer instanceof AllowNullValueTransformerInterface) {
                    continue;
                }

                $fieldValueExpr = new Expr\ConstFetch(new Name('null'));
            }

            $keyPrefix = new Expr\BinaryOp\Concat(
                $sepVar,
                new Scalar\String_('"' . $this->escapeKey($propertyMetadata->target->property) . '":'),
            );

            $propStatements = $this->propertyStatements($metadata, $propertyMetadata, $fieldValueExpr, $keyPrefix, $sepVar);

            $condition = $this->propertyConditionsGenerator->generate($metadata, $propertyMetadata);

            if ($condition) {
                $propStatements = [new Stmt\If_($condition, ['stmts' => $propStatements])];
            }

            $statements = [...$statements, ...$propStatements];
        }

        $statements[] = new Stmt\Expression(new Expr\Yield_(new Scalar\String_('}')));

        return $statements;
    }

    /**
     * @return Stmt[]
     */
    private function propertyStatements(
        GeneratorMetadata $metadata,
        PropertyMetadata $propertyMetadata,
        Expr $fieldValueExpr,
        Expr $keyPrefix,
        Expr\Variable $sepVar,
    ): array {
        $transformer = $propertyMetadata->transformer;
        $variableRegistry = $metadata->variableRegistry;
        $advanceSep = new Stmt\Expression(new Expr\Assign($sepVar, new Scalar\String_(',')));

        // A nested object mapped by a sub-mapper: stream it via the sub-mapper's own
        // mapToJsonStream() so nested collections stream too.
        if ($transformer instanceof ObjectTransformer
            && ($dependencyName = $this->streamableDependency($transformer)) !== null) {
            $valueVar = new Expr\Variable($variableRegistry->getUniqueVariableScope()->getUniqueName('jsonValue'));

            return [
                new Stmt\Expression(new Expr\Assign($valueVar, $fieldValueExpr)),
                new Stmt\Expression(new Expr\Yield_($keyPrefix)),
                $advanceSep,
                new Stmt\If_(new Expr\BinaryOp\Identical(new Expr\ConstFetch(new Name('null')), $valueVar), [
                    'stmts' => [new Stmt\Expression(new Expr\Yield_(new Scalar\String_('null')))],
                    'else' => new Stmt\Else_([$this->yieldFromMapper($dependencyName, $valueVar, $variableRegistry)]),
                ]),
            ];
        }

        // A list of objects mapped by a sub-mapper: stream `[` + each element + `]`.
        if (($itemTransformer = $this->objectListItemTransformer($transformer)) !== null
            && ($dependencyName = $this->streamableDependency($itemTransformer)) !== null) {
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
                            'else' => new Stmt\Else_([$this->yieldFromMapper($dependencyName, $itemVar, $variableRegistry)]),
                        ]),
                    ],
                ]),
                new Stmt\Expression(new Expr\Yield_(new Scalar\String_(']'))),
            ];
        }

        // Leaf value (scalar, date, enum, scalar list, dict, ...): transform then
        // json_encode the result in one native call.
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
        $propStatements[] = $advanceSep;

        return $propStatements;
    }

    private function yieldFromMapper(string $dependencyName, Expr $value, VariableRegistry $variableRegistry): Stmt
    {
        return new Stmt\Expression(new Expr\YieldFrom(new Expr\MethodCall(
            new Expr\ArrayDimFetch(
                new Expr\PropertyFetch(new Expr\Variable('this'), 'mappers'),
                new Scalar\String_($dependencyName),
            ),
            'mapToJsonStream',
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
     * The sub-mapper key when it is an object → array mapper (so it has a
     * mapToJsonStream() method), or null otherwise.
     */
    private function streamableDependency(ObjectTransformer $transformer): ?string
    {
        foreach ($transformer->getDependencies() as $dependency) {
            if ('array' !== $dependency->source && 'array' === $dependency->target) {
                return $dependency->name;
            }
        }

        return null;
    }

    private function escapeKey(string $key): string
    {
        // Keys are property names; encode to be safe and strip the surrounding quotes.
        return substr(json_encode($key, JSON_THROW_ON_ERROR), 1, -1);
    }
}
