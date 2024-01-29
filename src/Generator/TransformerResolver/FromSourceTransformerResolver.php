<?php

declare(strict_types=1);

namespace AutoMapper\Generator\TransformerResolver;

use AutoMapper\CustomTransformer\CustomTransformersRegistry;
use AutoMapper\Extractor\PropertyMapping;
use AutoMapper\MapperMetadata\MapperType;
use AutoMapper\Transformer\TransformerFactoryInterface;
use AutoMapper\Transformer\TransformerInterface;
use Symfony\Component\PropertyInfo\PropertyInfoExtractorInterface;
use Symfony\Component\PropertyInfo\Type;

final readonly class FromSourceTransformerResolver implements TransformerResolverInterface
{
    public function __construct(
        private PropertyInfoExtractorInterface $propertyInfoExtractor,
        private CustomTransformersRegistry $customTransformerRegistry,
        private TransformerFactoryInterface $transformerFactory,
    )
    {
    }

    public function resolveTransformer(PropertyMapping $propertyMapping): TransformerInterface|string|null
    {
        if ($propertyMapping->mapperMetadata->mapperType() !== MapperType::FROM_SOURCE) {
            return null;
        }

        $mapperMetadata = $propertyMapping->mapperMetadata;

        $sourceTypes = $this->propertyInfoExtractor->getTypes($mapperMetadata->getSource(), $propertyMapping->property);

        if (null === $sourceTypes) {
            $sourceTypes = [new Type(Type::BUILTIN_TYPE_NULL)]; // if no types found, we force a null type
        }

        $targetTypes = [];

        foreach ($sourceTypes as $type) {
            $targetType = $this->transformType($mapperMetadata->getTarget(), $type);

            if ($targetType) {
                $targetTypes[] = $targetType;
            }
        }

        return $this->customTransformerRegistry->getCustomTransformerClass($mapperMetadata, $sourceTypes, $targetTypes, $propertyMapping->property)
            ?? $this->transformerFactory->getTransformer($sourceTypes, $targetTypes, $mapperMetadata);
    }

    private function transformType(string $target, Type $type = null): ?Type
    {
        if (null === $type) {
            return null;
        }

        $builtinType = $type->getBuiltinType();
        $className = $type->getClassName();

        if (Type::BUILTIN_TYPE_OBJECT === $type->getBuiltinType() && \stdClass::class !== $type->getClassName()) {
            $builtinType = 'array' === $target ? Type::BUILTIN_TYPE_ARRAY : Type::BUILTIN_TYPE_OBJECT;
            $className = 'array' === $target ? null : \stdClass::class;
        }

        // Use string for datetime
        if (Type::BUILTIN_TYPE_OBJECT === $type->getBuiltinType() && (\DateTimeInterface::class === $type->getClassName() || is_subclass_of($type->getClassName(), \DateTimeInterface::class))) {
            $builtinType = 'string';
        }

        $collectionKeyTypes = $type->getCollectionKeyTypes();
        $collectionValueTypes = $type->getCollectionValueTypes();

        return new Type(
            $builtinType,
            $type->isNullable(),
            $className,
            $type->isCollection(),
            $this->transformType($target, $collectionKeyTypes[0] ?? null),
            $this->transformType($target, $collectionValueTypes[0] ?? null)
        );
    }
}
