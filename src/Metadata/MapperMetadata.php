<?php

declare(strict_types=1);

namespace AutoMapper\Metadata;

use AutoMapper\AutoMapper;

/**
 * @internal
 */
class MapperMetadata
{
    public string $className;
    public ?string $lazyGhostClassName;

    /** @var \ReflectionClass<object>|null */
    public ?\ReflectionClass $sourceReflectionClass;

    /** @var \ReflectionClass<object>|null */
    public ?\ReflectionClass $targetReflectionClass;

    /**
     * @param class-string<object>|'array' $source
     * @param class-string<object>|'array' $target
     */
    public function __construct(
        public string $source,
        public string $target,
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

        $this->className = sprintf('%s%s_%s', $this->classPrefix, str_replace('\\', '_', $this->source), str_replace('\\', '_', $this->target));

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

        $hash .= AutoMapper::VERSION_ID;

        return $hash;
    }
}
