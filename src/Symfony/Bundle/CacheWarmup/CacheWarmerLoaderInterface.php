<?php

declare(strict_types=1);

namespace AutoMapper\Symfony\Bundle\CacheWarmup;

interface CacheWarmerLoaderInterface
{
    /**
     * @return iterable<CacheWarmupData>
     */
    public function loadCacheWarmupData(): iterable;
}
