<?php

declare(strict_types=1);

namespace AutoMapper\Generator\TransformerResolver;

use AutoMapper\CustomTransformer\CustomTransformerGenerator;
use AutoMapper\CustomTransformer\CustomTransformersRegistry;
use AutoMapper\Extractor\PropertyMapping;

final readonly class FromAttributeTransformerResolver implements TransformerResolverInterface
{
    private CustomTransformerGenerator $customTransformerGenerator;

    public function __construct(
        CustomTransformersRegistry $customTransformerRegistry
    ) {
        $this->customTransformerGenerator = new CustomTransformerGenerator($customTransformerRegistry);
    }

    public function resolveTransformer(PropertyMapping $propertyMapping): string|null
    {
        if (null === ($propertyAttribute = $propertyMapping->getRelatedAttribute())) {
            return null;
        }

        $transformerClass = strtr('{attributeName}_Transformer_{source}_{target}_{sourceProperty}_{targetProperty}', [
            '{attributeName}' => (new \ReflectionClass($propertyMapping))->getShortName(),
            '{source}' => str_replace('\\', '_', $propertyMapping->mapperMetadata->getSource()),
            '{target}' => str_replace('\\', '_', $propertyMapping->mapperMetadata->getTarget()),
            '{sourceProperty}' => $propertyMapping->property,
            '{targetProperty}' => $propertyAttribute->propertyName,
        ]);

        if (!class_exists($transformerClass)) {
            $this->customTransformerGenerator->generateMapToCustomTransformer($propertyMapping, $propertyAttribute, $transformerClass);
        }

        return $transformerClass;
    }
}
