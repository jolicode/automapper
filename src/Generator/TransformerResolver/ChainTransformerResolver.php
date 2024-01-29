<?php

declare(strict_types=1);

namespace AutoMapper\Generator\TransformerResolver;

use AutoMapper\CustomTransformer\CustomTransformersRegistry;
use AutoMapper\Extractor\PropertyMapping;
use AutoMapper\Transformer\TransformerFactoryInterface;
use AutoMapper\Transformer\TransformerInterface;
use Symfony\Component\PropertyInfo\PropertyInfoExtractorInterface;

final readonly class ChainTransformerResolver implements TransformerResolverInterface
{
    /** @var list<TransformerResolverInterface> */
    private array $transformerResolvers;

    public function __construct(
        TransformerResolverInterface ...$transformerResolvers
    ) {
        $this->transformerResolvers = $transformerResolvers;
    }

    public static function create(
        PropertyInfoExtractorInterface $propertyInfoExtractor,
        CustomTransformersRegistry $customTransformerRegistry,
        TransformerFactoryInterface $transformerFactory,
    ): self {
        return new self(
            ...[
                new FromAttributeTransformerResolver($customTransformerRegistry),
                new FromSourceTransformerResolver(
                    $propertyInfoExtractor,
                    $customTransformerRegistry,
                    $transformerFactory
                ),
                new FromTargetTransformerResolver(
                    $propertyInfoExtractor,
                    $customTransformerRegistry,
                    $transformerFactory
                ),
                new SourceTargetTransformerResolver(
                    $propertyInfoExtractor,
                    $customTransformerRegistry,
                    $transformerFactory
                ),
            ]
        );
    }

    public function resolveTransformer(PropertyMapping $propertyMapping): TransformerInterface|string|null
    {
        foreach ($this->transformerResolvers as $transformerResolver) {
            if ($transformer = $transformerResolver->resolveTransformer($propertyMapping)) {
                return $transformer;
            }
        }

        return null;
    }
}
