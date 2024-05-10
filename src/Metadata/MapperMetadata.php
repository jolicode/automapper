<?php

declare(strict_types=1);

namespace AutoMapper\Metadata;

use AutoMapper\AutoMapper;

/**
 * @internal
 */
class MapperMetadata
{
    /** @var class-string<object> */
    public string $className;
    public ?string $lazyGhostClassName;

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
    ) {
        if (class_exists($this->source) && !\in_array($this->source, ['array', \stdClass::class], true)) {
            $reflectionSource = new \ReflectionClass($this->source);
            $this->sourceReflectionClass = $reflectionSource;
        } else {
            $this->sourceReflectionClass = null;
        }

        if (class_exists($this->target) && $this->target !== \stdClass::class) {
            $reflectionTarget = new \ReflectionClass($this->target);
            $this->targetReflectionClass = $reflectionTarget;
        } else {
            $this->targetReflectionClass = null;
        }

        /** @var class-string<object> $className */
        $className = sprintf('%s%s_%s', $this->classPrefix, str_replace('\\', '_', $this->source), str_replace('\\', '_', $this->target));
        $this->className = $className;

        if (null !== $this->targetReflectionClass && !$this->targetReflectionClass->isFinal() && !$this->targetReflectionClass->isAbstract() && !$this->targetReflectionClass->isReadOnly()) {
            $this->lazyGhostClassName = sprintf('%s%s_%s_LazyGhost', $this->classPrefix, str_replace('\\', '_', $this->source), str_replace('\\', '_', $this->target));
        } else {
            $this->lazyGhostClassName = null;
        }
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

        $hash .= AutoMapper::VERSION;

        return $hash;
    }
}
