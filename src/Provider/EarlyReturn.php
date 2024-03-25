<?php

declare(strict_types=1);

namespace AutoMapper\Provider;

/**
 * @experimental
 */
final class EarlyReturn
{
    /**
     * @param object|array<mixed>|null $value
     */
    public function __construct(
        public object|array|null $value
    ) {
    }
}
