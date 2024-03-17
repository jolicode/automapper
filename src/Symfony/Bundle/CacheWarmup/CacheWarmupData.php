<?php

declare(strict_types=1);

namespace AutoMapper\Symfony\Bundle\CacheWarmup;

final class CacheWarmupData
{
    /**
     * @param class-string<object>|'array' $source
     * @param class-string<object>|'array' $target
     */
    public function __construct(
        private string $source,
        private string $target,
    ) {
        if (!$this->isValid($source) || !$this->isValid($target)) {
            throw CacheWarmupDataException::sourceOrTargetDoesNoExist($source, $target);
        }

        if ($target === $source) {
            throw CacheWarmupDataException::sourceAndTargetAreEquals($source);
        }
    }

    /**
     * @param array{source: class-string<object>|'array', target: class-string<object>|'array'} $array
     */
    public static function fromArray(array $array): self
    {
        return new self($array['source'], $array['target']);
    }

    /**
     * @return class-string<object>|'array'
     */
    public function getSource(): string
    {
        return $this->source;
    }

    /**
     * @return class-string<object>|'array'
     */
    public function getTarget(): string
    {
        return $this->target;
    }

    private function isValid(string $arrayOrClass): bool
    {
        return $arrayOrClass === 'array' || class_exists($arrayOrClass);
    }
}
