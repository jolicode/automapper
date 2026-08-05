<?php

declare(strict_types=1);

namespace AutoMapper;

/**
 * @template Source of object|array<mixed>
 * @template Target of object|array<mixed>
 *
 * @phpstan-import-type MapperContextArray from MapperContext
 *
 * @internal
 *
 * @implements MapperInterface<Source, Target>
 */
class LazyMapper implements MapperInterface
{
    /** @var MapperInterface<Source, Target>|null */
    private ?MapperInterface $mapper = null;

    public function __construct(
        private readonly AutoMapperRegistryInterface $registry,
        /** @var 'array'|class-string<object> */
        private readonly string $source,
        /** @var 'array'|'json'|class-string<object> */
        private readonly string $target,
    ) {
    }

    public function getTargetIdentifiers(mixed $value): mixed
    {
        $mapper = $this->getMapper();

        if ($mapper instanceof GeneratedMapper) {
            return $mapper->getTargetIdentifiers($value);
        }

        return null;
    }

    public function getSourceHash(mixed $value): string
    {
        $mapper = $this->getMapper();

        if ($mapper instanceof GeneratedMapper) {
            return $mapper->getSourceHash($value);
        }

        return '';
    }

    public function getTargetHash(mixed $value): string
    {
        $mapper = $this->getMapper();

        if ($mapper instanceof GeneratedMapper) {
            return $mapper->getTargetHash($value);
        }

        return '';
    }

    public function &map(mixed $value, array $context = []): mixed
    {
        return $this->getMapper()->map($value, $context);
    }

    /**
     * @return MapperInterface<Source, Target>
     */
    public function getMapper(): MapperInterface
    {
        if ($this->mapper === null) {
            /** @var MapperInterface<Source, Target> $mapper */
            $mapper = $this->registry->getMapper($this->source, $this->target);

            if ($mapper instanceof GeneratedMapper) {
                $mapper->registerMappers($this->registry);
            }

            $this->mapper = $mapper;
        }

        return $this->mapper;
    }
}
