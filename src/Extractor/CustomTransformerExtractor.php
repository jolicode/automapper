<?php

declare(strict_types=1);

namespace AutoMapper\Extractor;

use AutoMapper\Transformer\CustomTransformer\CustomModelTransformerInterface;
use AutoMapper\Transformer\CustomTransformer\CustomTransformerInterface;
use PhpParser\Node\Arg;
use PhpParser\Node\Expr;

/**
 * @internal
 */
final readonly class CustomTransformerExtractor
{
    public function __construct(
        private ClassMethodToCallbackExtractor $extractor
    ) {
    }

    /**
     * @param class-string<CustomTransformerInterface> $customTransformerClass
     */
    public function extract(string $customTransformerClass, ?Expr $propertyToTransform, Expr $sourceObject): Expr
    {
        if (!$propertyToTransform && is_a($customTransformerClass, CustomModelTransformerInterface::class, allow_string: true)) {
            throw new \LogicException('CustomModelTransformerInterface must use $propertyToTransform.');
        }

        $arg = is_a($customTransformerClass, CustomModelTransformerInterface::class, allow_string: true)
            ? $propertyToTransform
            // let's pass the full object when using "property" custom transform for more flexibility
            : $sourceObject;

        return $this->extractor->extract(
            $customTransformerClass,
            'transform',
            [new Arg($arg)]
        );
    }
}
