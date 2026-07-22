<?php

declare(strict_types=1);

namespace AutoMapper\Metadata;

use AutoMapper\Configuration;

/**
 * @internal
 *
 * @implements \IteratorAggregate<MapperMetadata>
 */
class MetadataRegistry implements \IteratorAggregate, \Countable
{
    /** @var array<string, array<string, MapperMetadata>> */
    private array $registry = [];

    public function __construct(
        private readonly Configuration $configuration,
        ?self $subRegistry = null,
    ) {
        if (null !== $subRegistry) {
            $this->registry = $subRegistry->registry;
        }
    }

    /**
     * @param class-string<object>|'array'        $source
     * @param class-string<object>|'array'|'json' $target
     */
    public function get(string $source, string $target, bool $registered = false): MapperMetadata
    {
        $source = $this->getRealClassName($source);
        // `json` is a projection format that behaves like `array` for every metadata concern, so
        // it is kept as a plain array-like target downstream rather than a distinct type.
        /** @var class-string<object>|'array' $target */
        $target = 'json' === $target ? 'json' : $this->getRealClassName($target);

        return $this->registry[$source][$target] ??= new MapperMetadata($source, $target, $registered, $this->configuration->classPrefix);
    }

    /**
     * @param class-string<object>|'array'        $source
     * @param class-string<object>|'array'|'json' $target
     */
    public function register(string $source, string $target): void
    {
        $this->get($source, $target, true);
    }

    /**
     * @param class-string<object>|'array'        $source
     * @param class-string<object>|'array'|'json' $target
     */
    public function has(string $source, string $target, bool $onlyRegistered): bool
    {
        $source = $this->getRealClassName($source);
        /** @var class-string<object>|'array' $target */
        $target = 'json' === $target ? 'json' : $this->getRealClassName($target);

        if (!isset($this->registry[$source][$target])) {
            return false;
        }

        if ($onlyRegistered) {
            return $this->registry[$source][$target]->registered;
        }

        return true;
    }

    public function getIterator(): \Traversable
    {
        foreach ($this->registry as $targets) {
            foreach ($targets as $metadata) {
                yield $metadata;
            }
        }
    }

    /**
     * @param class-string<object>|'array' $className
     *
     * @return class-string<object>|'array'
     */
    private function getRealClassName(string $className): string
    {
        // Class names resolved from docblocks may have a leading backslash, normalize it away so
        // registry keys and generated code always use the same form
        /** @var class-string<object>|'array' $className */
        $className = ltrim($className, '\\');

        // __CG__: Doctrine Common Marker for Proxy (ODM < 2.0 and ORM < 3.0)
        // __PM__: Ocramius Proxy Manager (ODM >= 2.0)
        $positionCg = strrpos($className, '\\__CG__\\');
        $positionPm = strrpos($className, '\\__PM__\\');

        if (false === $positionCg && false === $positionPm) {
            return $className;
        }

        if (false !== $positionCg) {
            /** @var class-string<object> */
            return substr($className, $positionCg + 8);
        }

        /** @var class-string<object> */
        return substr(
            $className,
            8 + $positionPm,
            strrpos($className, '\\') - ($positionPm + 8)
        );
    }

    public function count(): int
    {
        return \count($this->registry, \COUNT_RECURSIVE);
    }
}
