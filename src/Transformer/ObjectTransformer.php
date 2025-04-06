<?php

declare(strict_types=1);

namespace AutoMapper\Transformer;

use AutoMapper\Generator\UniqueVariableScope;
use AutoMapper\MapperContext;
use AutoMapper\Metadata\PropertyMetadata;
use PhpParser\Node\Arg;
use PhpParser\Node\Expr;
use PhpParser\Node\Name;
use PhpParser\Node\Scalar;
use Symfony\Component\PropertyInfo\Type;

/**
 * Transform to an object which can be mapped by AutoMapper (sub mapping).
 *
 * @author Joel Wurtz <jwurtz@jolicode.com>
 *
 * @internal
 */
final class ObjectTransformer implements TransformerInterface, DependentTransformerInterface, AssignedByReferenceTransformerInterface, CheckTypeInterface, IdentifierHashInterface
{
    public function __construct(
        private readonly Type $sourceType,
        private readonly Type $targetType,
        public bool $deepTargetToPopulate = true,
    ) {
    }

    public function transform(Expr $input, Expr $target, PropertyMetadata $propertyMapping, UniqueVariableScope $uniqueVariableScope, Expr\Variable $source, ?Expr $existingValue = null): array
    {
        $mapperName = $this->getDependencyName();

        $newContextArgs = [
            new Arg(new Expr\Variable('context')),
            new Arg(new Scalar\String_($propertyMapping->source->property)),
        ];

        // ($context['deep_target_to_populate'] ?? false) ? $source->property : null
        if ($propertyMapping->target->readAccessor !== null && $this->deepTargetToPopulate) {
            $isDefined = $propertyMapping->target->readAccessor->getIsDefinedExpression(new Expr\Variable('result'));
            $existingValue = $propertyMapping->target->readAccessor->getExpression(new Expr\Variable('result'));

            if (null !== $isDefined) {
                $existingValue = new Expr\Ternary(
                    $isDefined,
                    $existingValue,
                    new Expr\ConstFetch(new Name('null'))
                );
            }

            $newContextArgs[] = new Arg(
                new Expr\Ternary(
                    new Expr\BinaryOp\Coalesce(
                        new Expr\ArrayDimFetch(new Expr\Variable('context'), new Scalar\String_(MapperContext::DEEP_TARGET_TO_POPULATE)),
                        new Expr\ConstFetch(new Name('false'))
                    ),
                    $existingValue,
                    new Expr\ConstFetch(new Name('null'))
                )
            );
        } elseif ($existingValue !== null) {
            $newContextArgs[] = new Arg($existingValue);
        }

        /*
         * Use a sub mapper to map the property
         *
         * $this->mappers['Mapper_SourceType_TargetType']->map($input, MapperContext::withNewContext($context, $propertyMapping->property));
         */
        return [new Expr\MethodCall(new Expr\ArrayDimFetch(
            new Expr\PropertyFetch(new Expr\Variable('this'), 'mappers'),
            new Scalar\String_($mapperName)
        ), 'map', [
            new Arg($input),
            new Arg(new Expr\StaticCall(new Name\FullyQualified(MapperContext::class), 'withNewContext', $newContextArgs)),
        ]), []];
    }

    public function getCheckExpression(Expr $input, Expr $target, PropertyMetadata $propertyMapping, UniqueVariableScope $uniqueVariableScope, Expr\Variable $source): ?Expr
    {
        if ($this->sourceType->getClassName() !== null) {
            return new Expr\Instanceof_($input, new Name\FullyQualified($this->sourceType->getClassName()));
        }

        return new Expr\FuncCall(
            new Name('is_object'),
            [
                new Arg($input),
            ]
        );
    }

    public function assignByRef(): bool
    {
        return true;
    }

    public function getDependencies(): array
    {
        return [new MapperDependency($this->getDependencyName(), $this->getSource(), $this->getTarget())];
    }

    private function getDependencyName(): string
    {
        return 'Mapper_' . $this->getSource() . '_' . $this->getTarget();
    }

    /**
     * @return class-string<object>|'array'
     */
    private function getSource(): string
    {
        $sourceTypeName = 'array';

        if (Type::BUILTIN_TYPE_OBJECT === $this->sourceType->getBuiltinType()) {
            /**
             * Cannot be null since we check the source type is an Object.
             *
             * @var class-string<object> $sourceTypeName
             */
            $sourceTypeName = $this->sourceType->getClassName();
        }

        return $sourceTypeName;
    }

    /**
     * @return class-string<object>|'array'
     */
    private function getTarget(): string
    {
        $targetTypeName = 'array';

        if (Type::BUILTIN_TYPE_OBJECT === $this->targetType->getBuiltinType()) {
            /**
             * Cannot be null since we check the target type is an Object.
             *
             * @var class-string<object> $targetTypeName
             */
            $targetTypeName = $this->targetType->getClassName();
        }

        return $targetTypeName;
    }

    public function getSourceHashExpression(Expr $source): Expr
    {
        $mapperName = $this->getDependencyName();

        return new Expr\MethodCall(new Expr\ArrayDimFetch(
            new Expr\PropertyFetch(new Expr\Variable('this'), 'mappers'),
            new Scalar\String_($mapperName)
        ), 'getSourceHash', [
            new Arg($source),
        ]);
    }

    public function getTargetHashExpression(Expr $target): Expr
    {
        $mapperName = $this->getDependencyName();

        return new Expr\MethodCall(new Expr\ArrayDimFetch(
            new Expr\PropertyFetch(new Expr\Variable('this'), 'mappers'),
            new Scalar\String_($mapperName)
        ), 'getTargetHash', [
            new Arg($target),
        ]);
    }
}
