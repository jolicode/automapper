<?php

declare(strict_types=1);

namespace AutoMapper\Transformer;

use AutoMapper\Generator\UniqueVariableScope;
use AutoMapper\Metadata\PropertyMetadata;
use PhpParser\Node\Arg;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\Cast;
use PhpParser\Node\Name;
use Symfony\Component\TypeInfo\Type;
use Symfony\Component\TypeInfo\TypeIdentifier;

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
        TypeIdentifier::BOOL->value => [
            TypeIdentifier::INT->value => Cast\Int_::class,
            TypeIdentifier::STRING->value => Cast\String_::class,
            TypeIdentifier::FLOAT->value => 'toFloat',
            TypeIdentifier::ARRAY->value => 'toArray',
            TypeIdentifier::ITERABLE->value => 'toArray',
        ],
        TypeIdentifier::MIXED->value => [
            TypeIdentifier::INT->value => Cast\Int_::class,
            TypeIdentifier::STRING->value => Cast\String_::class,
            TypeIdentifier::FLOAT->value => 'toFloat',
            TypeIdentifier::ARRAY->value => 'toArray',
            TypeIdentifier::ITERABLE->value => 'toArray',
        ],
        TypeIdentifier::FLOAT->value => [
            TypeIdentifier::STRING->value => Cast\String_::class,
            TypeIdentifier::INT->value => Cast\Int_::class,
            TypeIdentifier::BOOL->value => Cast\Bool_::class,
            TypeIdentifier::ARRAY->value => 'toArray',
            TypeIdentifier::ITERABLE->value => 'toArray',
        ],
        TypeIdentifier::INT->value => [
            TypeIdentifier::FLOAT->value => 'toFloat',
            TypeIdentifier::STRING->value => Cast\String_::class,
            TypeIdentifier::BOOL->value => Cast\Bool_::class,
            TypeIdentifier::ARRAY->value => 'toArray',
            TypeIdentifier::ITERABLE->value => 'toArray',
        ],
        TypeIdentifier::ITERABLE->value => [
            TypeIdentifier::ARRAY->value => 'fromIteratorToArray',
        ],
        TypeIdentifier::ARRAY->value => [],
        TypeIdentifier::STRING->value => [
            TypeIdentifier::ARRAY->value => 'toArray',
            TypeIdentifier::ITERABLE->value => 'toArray',
            TypeIdentifier::FLOAT->value => 'toFloat',
            TypeIdentifier::INT->value => Cast\Int_::class,
            TypeIdentifier::BOOL->value => Cast\Bool_::class,
        ],
        TypeIdentifier::CALLABLE->value => [],
        TypeIdentifier::RESOURCE->value => [],
    ];

    private const CONDITION_MAPPING = [
        TypeIdentifier::BOOL->value => 'is_bool',
        TypeIdentifier::INT->value => 'is_int',
        TypeIdentifier::FLOAT->value => 'is_float',
        TypeIdentifier::STRING->value => 'is_string',
        TypeIdentifier::ARRAY->value => 'is_array',
        TypeIdentifier::OBJECT->value => 'is_object',
        TypeIdentifier::RESOURCE->value => 'is_resource',
        TypeIdentifier::CALLABLE->value => 'is_callable',
        TypeIdentifier::ITERABLE->value => 'is_iterable',
    ];

    /**
     * @param Type\BuiltinType<TypeIdentifier> $sourceType
     */
    public function __construct(
        private Type\BuiltinType $sourceType,
        private Type $targetType,
    ) {
    }

    public function transform(Expr $input, Expr $target, PropertyMetadata $propertyMapping, UniqueVariableScope $uniqueVariableScope, Expr $source, ?Expr $existingValue = null): array
    {
        $targetTypes = [];

        foreach ($this->targetType->traverse() as $type) {
            if ($type instanceof Type\BuiltinType) {
                $targetTypes[] = $type->getTypeIdentifier()->value;

                if ($type->getTypeIdentifier() === $this->sourceType->getTypeIdentifier()) {
                    /* Output type can be the same as input type so we simply return the same expression */
                    return [$input, []];
                }
            }
        }

        if (!$targetTypes) {
            /* When there is no possibility to cast we assume that the mutator will be able to handle the value */
            return [$input, []];
        }

        foreach (self::CAST_MAPPING[$this->sourceType->getTypeIdentifier()->value] as $castType => $castMethod) {
            if (\in_array($castType, $targetTypes, true)) {
                if (method_exists($this, $castMethod)) {
                    /*
                     * Use specific cast expression if callback exist in this class
                     *
                     * $array = [$source->property];
                     */
                    return [$this->$castMethod($input), []];
                }

                if (!class_exists($castMethod)) {
                    continue;
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

    public function getCheckExpression(Expr $input, Expr $target, PropertyMetadata $propertyMapping, UniqueVariableScope $uniqueVariableScope, Expr $source): ?Expr
    {
        if ($this->sourceType->getTypeIdentifier() === TypeIdentifier::NULL) {
            return null;
        }

        if (!isset(self::CONDITION_MAPPING[$this->sourceType->getTypeIdentifier()->value])) {
            return null;
        }

        return new Expr\FuncCall(
            new Name(self::CONDITION_MAPPING[$this->sourceType->getTypeIdentifier()->value]),
            [
                new Arg($input),
            ]
        );
    }

    private function toArray(Expr $input): Expr
    {
        return new Expr\Array_([create_expr_array_item($input)]);
    }

    private function toFloat(Expr $input): Expr
    {
        return new Cast\Double($input, ['kind' => Cast\Double::KIND_FLOAT]);
    }

    private function fromIteratorToArray(Expr $input): Expr
    {
        return new Expr\FuncCall(new Name('iterator_to_array'), [
            new Arg($input),
        ]);
    }
}
