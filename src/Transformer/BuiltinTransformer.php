<?php

declare(strict_types=1);

namespace AutoMapper\Transformer;

use AutoMapper\Generator\UniqueVariableScope;
use AutoMapper\Metadata\PropertyMetadata;
use PhpParser\Node\Arg;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\Cast;
use PhpParser\Node\Name;
use Symfony\Component\PropertyInfo\Type;

use function AutoMapper\PhpParser\create_expr_array_item;

/**
 * Built in transformer to handle PHP scalar types.
 *
 * @author Joel Wurtz <jwurtz@jolicode.com>
 *
 * @internal
 */
final readonly class BuiltinTransformer implements TransformerInterface, CheckTypeInterface
{
    private const CAST_MAPPING = [
        Type::BUILTIN_TYPE_BOOL => [
            Type::BUILTIN_TYPE_INT => Cast\Int_::class,
            Type::BUILTIN_TYPE_STRING => Cast\String_::class,
            Type::BUILTIN_TYPE_FLOAT => Cast\Double::class,
            Type::BUILTIN_TYPE_ARRAY => 'toArray',
            Type::BUILTIN_TYPE_ITERABLE => 'toArray',
        ],
        Type::BUILTIN_TYPE_FLOAT => [
            Type::BUILTIN_TYPE_STRING => Cast\String_::class,
            Type::BUILTIN_TYPE_INT => Cast\Int_::class,
            Type::BUILTIN_TYPE_BOOL => Cast\Bool_::class,
            Type::BUILTIN_TYPE_ARRAY => 'toArray',
            Type::BUILTIN_TYPE_ITERABLE => 'toArray',
        ],
        Type::BUILTIN_TYPE_INT => [
            Type::BUILTIN_TYPE_FLOAT => Cast\Double::class,
            Type::BUILTIN_TYPE_STRING => Cast\String_::class,
            Type::BUILTIN_TYPE_BOOL => Cast\Bool_::class,
            Type::BUILTIN_TYPE_ARRAY => 'toArray',
            Type::BUILTIN_TYPE_ITERABLE => 'toArray',
        ],
        Type::BUILTIN_TYPE_ITERABLE => [
            Type::BUILTIN_TYPE_ARRAY => 'fromIteratorToArray',
        ],
        Type::BUILTIN_TYPE_ARRAY => [],
        Type::BUILTIN_TYPE_STRING => [
            Type::BUILTIN_TYPE_ARRAY => 'toArray',
            Type::BUILTIN_TYPE_ITERABLE => 'toArray',
            Type::BUILTIN_TYPE_FLOAT => Cast\Double::class,
            Type::BUILTIN_TYPE_INT => Cast\Int_::class,
            Type::BUILTIN_TYPE_BOOL => Cast\Bool_::class,
        ],
        Type::BUILTIN_TYPE_CALLABLE => [],
        Type::BUILTIN_TYPE_RESOURCE => [],
    ];

    private const CONDITION_MAPPING = [
        Type::BUILTIN_TYPE_BOOL => 'is_bool',
        Type::BUILTIN_TYPE_INT => 'is_int',
        Type::BUILTIN_TYPE_FLOAT => 'is_float',
        Type::BUILTIN_TYPE_STRING => 'is_string',
        Type::BUILTIN_TYPE_ARRAY => 'is_array',
        Type::BUILTIN_TYPE_OBJECT => 'is_object',
        Type::BUILTIN_TYPE_RESOURCE => 'is_resource',
        Type::BUILTIN_TYPE_CALLABLE => 'is_callable',
        Type::BUILTIN_TYPE_ITERABLE => 'is_iterable',
    ];

    public function __construct(
        private Type $sourceType,
        /** @var Type[] $targetTypes */
        private array $targetTypes,
    ) {
    }

    public function transform(Expr $input, Expr $target, PropertyMetadata $propertyMapping, UniqueVariableScope $uniqueVariableScope, Expr\Variable $source, ?Expr $existingValue = null): array
    {
        $targetTypes = array_map(function (Type $type) {
            return $type->getBuiltinType();
        }, $this->targetTypes);

        if (\in_array($this->sourceType->getBuiltinType(), $targetTypes, true)) {
            /* Output type is the same as input type so we simply return the same expression */
            return [$input, []];
        }

        foreach (self::CAST_MAPPING[$this->sourceType->getBuiltinType()] as $castType => $castMethod) {
            if (\in_array($castType, $targetTypes, true)) {
                if (method_exists($this, $castMethod)) {
                    /*
                     * Use specific cast expression if callback exist in this class
                     *
                     * $array = [$source->property];
                     */
                    return [$this->$castMethod($input), []];
                }

                /*
                 * Use the cast expression find in the cast matrix
                 *
                 * $bool = (bool) $source->int;
                 */
                return [new $castMethod($input), []];
            }
        }

        /* When there is no possibility to cast we assume that the mutator will be able to handle the value */
        return [$input, []];
    }

    public function getCheckExpression(Expr $input, Expr $target, PropertyMetadata $propertyMapping, UniqueVariableScope $uniqueVariableScope, Expr\Variable $source): ?Expr
    {
        if ($this->sourceType->getBuiltinType() === Type::BUILTIN_TYPE_NULL) {
            return null;
        }

        $condition = new Expr\FuncCall(
            new Name(self::CONDITION_MAPPING[$this->sourceType->getBuiltinType()]),
            [
                new Arg($input),
            ]
        );

        if ($this->sourceType->getBuiltinType() === Type::BUILTIN_TYPE_OBJECT && \is_string($this->sourceType->getClassName())) {
            $condition = new Expr\BinaryOp\BooleanAnd(
                $condition,
                new Expr\Instanceof_($input, new Name\FullyQualified($this->sourceType->getClassName()))
            );
        }

        return $condition;
    }

    private function toArray(Expr $input): Expr
    {
        return new Expr\Array_([create_expr_array_item($input)]);
    }

    private function fromIteratorToArray(Expr $input): Expr
    {
        return new Expr\FuncCall(new Name('iterator_to_array'), [
            new Arg($input),
        ]);
    }
}
