<?php

declare(strict_types=1);

namespace AutoMapper\Transformer;

use AutoMapper\Generator\UniqueVariableScope;
use AutoMapper\Metadata\PropertyMetadata;
use PhpParser\Node\Arg;
use PhpParser\Node\Expr;
use PhpParser\Node\Name;
use PhpParser\Node\Scalar;
use PhpParser\Node\Stmt;

/**
 * Transformer for array shape types (array{key: type, ...}).
 *
 * Applies per-key sub-transformers instead of a uniform loop.
 *
 * @internal
 */
final readonly class ArrayShapeTransformer implements \Stringable, TransformerInterface, DependentTransformerInterface
{
    /**
     * @param array<string|int, array{transformer: TransformerInterface, optional: bool}> $fieldTransformers
     */
    public function __construct(
        private array $fieldTransformers,
    ) {
    }

    public function transform(Expr $input, Expr $target, PropertyMetadata $propertyMapping, UniqueVariableScope $uniqueVariableScope, Expr $source, ?Expr $existingValue = null): array
    {
        $valuesVar = new Expr\Variable($uniqueVariableScope->getUniqueName('values'));

        $statements = [
            new Stmt\Expression(new Expr\Assign($valuesVar, new Expr\Array_())),
        ];

        foreach ($this->fieldTransformers as $key => $field) {
            $keyExpr = \is_int($key) ? new Scalar\Int_($key) : new Scalar\String_($key);
            $fieldInput = new Expr\ArrayDimFetch($input, $keyExpr);

            [$fieldOutput, $fieldStatements] = $field['transformer']->transform(
                $fieldInput,
                $target,
                $propertyMapping,
                $uniqueVariableScope,
                $source,
                null,
            );

            $assignStmt = new Stmt\Expression(
                new Expr\Assign(new Expr\ArrayDimFetch($valuesVar, $keyExpr), $fieldOutput)
            );
            $fieldStatements[] = $assignStmt;

            if ($field['optional']) {
                $statements[] = new Stmt\If_(
                    new Expr\FuncCall(new Name('array_key_exists'), [
                        new Arg($keyExpr),
                        new Arg($input),
                    ]),
                    ['stmts' => $fieldStatements],
                );
            } else {
                array_push($statements, ...$fieldStatements);
            }
        }

        return [$valuesVar, $statements];
    }

    public function getDependencies(): array
    {
        $dependencies = [];

        foreach ($this->fieldTransformers as $field) {
            if ($field['transformer'] instanceof DependentTransformerInterface) {
                array_push($dependencies, ...$field['transformer']->getDependencies());
            }
        }

        return $dependencies;
    }

    public function __toString(): string
    {
        $fields = [];
        foreach ($this->fieldTransformers as $key => $field) {
            $transformerName = $field['transformer'] instanceof \Stringable
                ? (string) $field['transformer']
                : \get_class($field['transformer']);
            $fields[] = \sprintf('%s: %s', $key, $transformerName);
        }

        return \sprintf('ArrayShapeTransformer{%s}', implode(', ', $fields));
    }
}
