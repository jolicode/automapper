<?php

declare(strict_types=1);

namespace AutoMapper\CustomTransformer;

/**
 * @experimental
 */
interface PrioritizedCustomTransformerInterface
{
    public function getPriority(): int;
}
