<?php

declare(strict_types=1);

namespace AutoMapper\Symfony\Bundle\CacheWarmup;

use AutoMapper\Loader\ClassLoaderInterface;
use AutoMapper\Metadata\MetadataFactory;
use AutoMapper\Metadata\MetadataRegistry;
use Symfony\Component\HttpKernel\CacheWarmer\CacheWarmerInterface;

/**
 * @internal
 */
final class CacheWarmer implements CacheWarmerInterface
{
    public function __construct(
        private readonly MetadataRegistry $mapping,
        private readonly MetadataFactory $metadataFactory,
        private readonly ClassLoaderInterface $classLoader,
        private readonly string $autoMapperCacheDirectory
    ) {
    }

    public function isOptional(): bool
    {
        return true;
    }

    public function warmUp(string $cacheDir, ?string $buildDir = null): array
    {
        // load all mappers
        $mapping = clone $this->mapping;
        $this->metadataFactory->resolveAllMetadata($mapping);

        if (\count($mapping) === 0) {
            return [];
        }

        if (!$this->classLoader->buildMappers($mapping)) {
            return [];
        }

        return array_map(
            function ($mapperMetadata) {
                return sprintf('%s/%s.php', $this->autoMapperCacheDirectory, $mapperMetadata->className);
            },
            iterator_to_array($mapping),
        );
    }
}
