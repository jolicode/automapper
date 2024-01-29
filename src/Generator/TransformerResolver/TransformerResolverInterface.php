<?php

declare(strict_types=1);

namespace AutoMapper\Generator\TransformerResolver;

use AutoMapper\CustomTransformer\CustomTransformerInterface;
use AutoMapper\Extractor\PropertyMapping;
use AutoMapper\Transformer\TransformerInterface;

interface TransformerResolverInterface
{
    /**
     * @return TransformerInterface|class-string<CustomTransformerInterface>|null
     */
    public function resolveTransformer(PropertyMapping $propertyMapping): TransformerInterface|string|null;
}
