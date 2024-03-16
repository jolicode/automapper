<?php

declare(strict_types=1);

namespace AutoMapper\Symfony\Bundle\CacheWarmup;

use AutoMapper\AutoMapperRegistryInterface;
use Symfony\Component\HttpKernel\CacheWarmer\CacheWarmerInterface;

/**
 * @internal
 */
final class CacheWarmer implements CacheWarmerInterface
{
    /** @param iterable<CacheWarmerLoaderInterface> $cacheWarmerLoaders */
    public function __construct(
        private readonly AutoMapperRegistryInterface $autoMapperRegistry,
        private readonly iterable $cacheWarmerLoaders,
        private readonly string $autoMapperCacheDirectory
    ) {
    }

    public function isOptional(): bool
    {
        return false;
    }

    public function warmUp(string $cacheDir, string $buildDir = null): array
    {
        foreach ($this->cacheWarmerLoaders as $cacheWarmerLoader) {
            foreach ($cacheWarmerLoader->loadCacheWarmupData() as $cacheWarmupData) {
                $mapper = $this->autoMapperRegistry->getMapper($cacheWarmupData->getSource(), $cacheWarmupData->getTarget());
            }
        }

        // preloaded files must be in cache directory
        if (!str_starts_with($this->autoMapperCacheDirectory, $cacheDir)) {
            return [];
        }

        $registryFile = sprintf('%s/registry.php', $this->autoMapperCacheDirectory);
        if (!file_exists($registryFile)) {
            return [];
        }

        $mappers = array_keys(require $registryFile);

        return array_map(
            function ($mapper) {
                return sprintf('%s/%s.php', $this->autoMapperCacheDirectory, $mapper);
            },
            $mappers
        );
    }
}
