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

final readonly class FromTargetTransformerResolver implements TransformerResolverInterface
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
        if ($propertyMapping->mapperMetadata->mapperType() !== MapperType::FROM_TARGET) {
            return null;
        }

        $mapperMetadata = $propertyMapping->mapperMetadata;

        $targetTypes = $this->propertyInfoExtractor->getTypes($mapperMetadata->getTarget(), $propertyMapping->property);

        if (null === $targetTypes) {
            return null;
        }

        $sourceTypes = [];

        foreach ($targetTypes as $type) {
            $sourceType = $this->transformType($mapperMetadata->getSource(), $type);

            if ($sourceType) {
                $sourceTypes[] = $sourceType;
            }
        }

        return $this->customTransformerRegistry->getCustomTransformerClass($mapperMetadata, $sourceTypes, $targetTypes, $propertyMapping->property)
            ?? $this->transformerFactory->getTransformer($sourceTypes, $targetTypes, $mapperMetadata);
    }

    private function transformType(string $source, ?Type $type = null): ?Type
    {
        if (null === $type) {
            return null;
        }

        $builtinType = $type->getBuiltinType();
        $className = $type->getClassName();

        if (Type::BUILTIN_TYPE_OBJECT === $type->getBuiltinType() && \stdClass::class !== $type->getClassName()) {
            $builtinType = 'array' === $source ? Type::BUILTIN_TYPE_ARRAY : Type::BUILTIN_TYPE_OBJECT;
            $className = 'array' === $source ? null : \stdClass::class;
        }

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
            $this->transformType($source, $collectionKeyTypes[0] ?? null),
            $this->transformType($source, $collectionValueTypes[0] ?? null)
        );
    }
}
