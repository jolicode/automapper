<?php

declare(strict_types=1);

namespace AutoMapper\Transformer;

use AutoMapper\Generator\UniqueVariableScope;
use AutoMapper\Metadata\PropertyMetadata;
use PhpParser\Node\Arg;
use PhpParser\Node\Expr;
use PhpParser\Node\Name;
use PhpParser\Node\Stmt;
use Symfony\Component\PropertyInfo\Type;

/**
 * Multiple transformer decorator.
 *
 * Decorate transformers with condition to handle property with multiples source types
 * It will always use the first target type possible for transformation
 *
 * @author Joel Wurtz <jwurtz@jolicode.com>
 *
 * @internal
 */
final class MultipleTransformer implements TransformerInterface, DependentTransformerInterface
{
    private const CONDITION_MAPPING = [
        Type::BUILTIN_TYPE_BOOL => 'is_bool',
        Type::BUILTIN_TYPE_INT => 'is_int',
        Type::BUILTIN_TYPE_FLOAT => 'is_float',
        Type::BUILTIN_TYPE_STRING => 'is_string',
        Type::BUILTIN_TYPE_NULL => 'is_null',
        Type::BUILTIN_TYPE_ARRAY => 'is_array',
        Type::BUILTIN_TYPE_OBJECT => 'is_object',
        Type::BUILTIN_TYPE_RESOURCE => 'is_resource',
        Type::BUILTIN_TYPE_CALLABLE => 'is_callable',
        Type::BUILTIN_TYPE_ITERABLE => 'is_iterable',
    ];

    /**
     * @param array<array{transformer: TransformerInterface, type: Type}> $transformers
     */
    public function __construct(
        private readonly array $transformers,
    ) {
    }

    public function transform(Expr $input, Expr $target, PropertyMetadata $propertyMapping, UniqueVariableScope $uniqueVariableScope, Expr\Variable $source): array
    {
        $output = new Expr\Variable($uniqueVariableScope->getUniqueName('value'));
        $statements = [
            new Stmt\Expression(new Expr\Assign($output, $input)),
        ];

        /*
         * In case of the source type can be mixed we need to check the type before doing the transformation.
         *
         *  if (is_bool($input)) {
         *     $output = $input;
         *  }
         *
         *  if (is_int($input)) {
         *     $output = (bool) $input;
         *  }
         *
         *  if (is_object($input) && $input instanceof SomeClass::class) {
         *     $output = [...expression from transformer...];
         *  }
         *
         */
        foreach ($this->transformers as $transformerData) {
            $transformer = $transformerData['transformer'];
            $type = $transformerData['type'];

            [$transformerOutput, $transformerStatements] = $transformer->transform($input, $target, $propertyMapping, $uniqueVariableScope, $source);

            $assignClass = ($transformer instanceof AssignedByReferenceTransformerInterface && $transformer->assignByRef()) ? Expr\AssignRef::class : Expr\Assign::class;

            $condition = new Expr\FuncCall(
                new Name(self::CONDITION_MAPPING[$type->getBuiltinType()]),
                [
                    new Arg($input),
                ]
            );

            if ($type->getBuiltinType() === Type::BUILTIN_TYPE_OBJECT) {
                $condition = new Expr\BinaryOp\BooleanAnd(
                    $condition,
                    new Expr\Instanceof_($input, new Name\FullyQualified($type->getClassName())) // @phpstan-ignore-line $type->getClassName() cannot be null here
                );
            }

            $statements[] = new Stmt\If_(
                $condition,
                [
                    'stmts' => array_merge(
                        $transformerStatements, [
                            new Stmt\Expression(new $assignClass($output, $transformerOutput)),
                        ]
                    ),
                ]
            );
        }

        return [$output, $statements];
    }

    public function getDependencies(): array
    {
        $dependencies = [];

        foreach ($this->transformers as $transformerData) {
            if ($transformerData['transformer'] instanceof DependentTransformerInterface) {
                $dependencies = array_merge($dependencies, $transformerData['transformer']->getDependencies());
            }
        }

        return $dependencies;
    }
}
