<?php

declare(strict_types=1);

namespace AutoMapper\Transformer\PropertyTransformer;

/**
 * @experimental
 */
interface PrioritizedPropertyTransformerInterface
{
    public function getPriority(): int;
}
