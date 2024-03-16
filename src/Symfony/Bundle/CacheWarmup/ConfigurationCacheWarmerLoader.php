<?php

declare(strict_types=1);

namespace AutoMapper\Symfony\Bundle\CacheWarmup;

/**
 * @internal
 */
final class ConfigurationCacheWarmerLoader implements CacheWarmerLoaderInterface
{
    /**
     * @param list<array{source: class-string<object>|'array', target: class-string<object>|'array'}> $mappersToGenerateOnWarmup
     */
    public function __construct(
        private array $mappersToGenerateOnWarmup
    ) {
    }

    public function loadCacheWarmupData(): iterable
    {
        foreach ($this->mappersToGenerateOnWarmup as $mapperToGenerate) {
            yield CacheWarmupData::fromArray($mapperToGenerate);
        }
    }
}
