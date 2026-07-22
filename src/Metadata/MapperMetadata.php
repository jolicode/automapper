<?php

declare(strict_types=1);

namespace AutoMapper\Metadata;

use AutoMapper\Lazy\LazyMapInterface;
use Composer\InstalledVersions;

class MapperMetadata
{
    /** @var class-string<object> */
    public string $className;

    /** @var \ReflectionClass<object>|null */
    public readonly ?\ReflectionClass $sourceReflectionClass;

    /** @var \ReflectionClass<object>|null */
    public readonly ?\ReflectionClass $targetReflectionClass;

    /**
     * @param class-string<object>|'array' $source
     * @param class-string<object>|'array' $target
     */
    public function __construct(
        public string $source,
        public string $target,
        public bool $registered,
        private string $classPrefix = 'Mapper_',
        public ?string $dateTimeFormat = null,
    ) {
        if ((class_exists($this->source) || interface_exists($this->source)) && $this->source !== \stdClass::class) {
            $reflectionSource = new \ReflectionClass($this->source);
            $this->sourceReflectionClass = $reflectionSource;
        } else {
            $this->sourceReflectionClass = null;
        }

        if ((class_exists($this->target) || interface_exists($this->target)) && $this->target !== \stdClass::class) {
            $reflectionTarget = new \ReflectionClass($this->target);
            $this->targetReflectionClass = $reflectionTarget;
        } else {
            $this->targetReflectionClass = null;
        }

        /** @var class-string<object> $className */
        $className = \sprintf('%s%s_%s', $this->classPrefix, $this->formatSourceTarget($this->source, $this->sourceReflectionClass?->isAnonymous() ?? false), $this->formatSourceTarget($this->target, $this->targetReflectionClass?->isAnonymous() ?? false));
        $this->className = $className;
    }

    public function isJsonTarget(): bool
    {
        return 'json' === $this->target;
    }

    /**
     * Whether the target is built by projecting the source (array shape) rather than hydrating a
     * class. `json` behaves like `array` here: it is the same object → array projection, only
     * serialized instead of assigned.
     */
    public function isTargetArrayLike(): bool
    {
        return \in_array($this->target, ['array', \stdClass::class, 'json'], true) || is_a($this->target, LazyMapInterface::class, true);
    }

    /**
     * Whether the source is read as an array shape rather than an object.
     */
    public function isSourceArrayLike(): bool
    {
        return \in_array($this->source, ['array', \stdClass::class], true) || is_a($this->source, LazyMapInterface::class, true);
    }

    public function getHash(): string
    {
        $hash = '';

        if ($reflection = $this->sourceReflectionClass) {
            if ($filename = $reflection->getFileName()) {
                $hash .= filemtime($filename);
            } else {
                $hash .= PHP_VERSION;
            }
        }

        if ($reflection = $this->targetReflectionClass) {
            if ($filename = $reflection->getFileName()) {
                $hash .= filemtime($filename);
            } else {
                $hash .= PHP_VERSION;
            }
        }

        $hash .= InstalledVersions::getVersion('jolicode/automapper');

        return $hash;
    }

    private function formatSourceTarget(string $name, bool $isAnonymous): string
    {
        if ($isAnonymous) {
            return 'Anonymous';
        }

        return str_replace('\\', '_', $name);
    }
}
