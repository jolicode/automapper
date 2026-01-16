<?php

declare(strict_types=1);

namespace AutoMapper\ObjectMapper;

use AutoMapper\AutoMapper;
use AutoMapper\AutoMapperInterface;
use Psr\Container\ContainerInterface;
use Symfony\Component\ObjectMapper\Exception\MappingException;
use Symfony\Component\ObjectMapper\Metadata\Mapping;
use Symfony\Component\ObjectMapper\Metadata\ObjectMapperMetadataFactoryInterface;
use Symfony\Component\ObjectMapper\Metadata\ReflectionObjectMapperMetadataFactory;
use Symfony\Component\ObjectMapper\ObjectMapperInterface;

final readonly class ObjectMapper implements ObjectMapperInterface
{
    private AutoMapperInterface $autoMapper;

    public function __construct(
        private ObjectMapperMetadataFactoryInterface $metadataFactory = new ReflectionObjectMapperMetadataFactory(),
        ?AutoMapperInterface $autoMapper = null,
        private ?ContainerInterface $conditionCallableLocator = null,
    ) {
        $this->autoMapper = $autoMapper ?? AutoMapper::create();
    }

    public function map(object $source, object|string|null $target = null): object
    {
        if (null === $target) {
            $metadata = $this->metadataFactory->create($source);
            $map = $this->getMapTarget($metadata, null, $source, null);
            $target = $map?->target;
        }

        if (!$target) {
            throw new MappingException(\sprintf('Mapping target not found for source "%s".', get_debug_type($source)));
        }

        if (\is_string($target) && !class_exists($target)) {
            throw new MappingException(\sprintf('Mapping target class "%s" does not exist for source "%s".', $target, get_debug_type($source)));
        }

        return $this->autoMapper->map($source, $target);
    }

    /**
     * @param callable(): mixed $fn
     */
    private function call(callable $fn, mixed $value, object $source, ?object $target = null): mixed
    {
        if (\is_string($fn)) {
            return \call_user_func($fn, $value);
        }

        return $fn($value, $source, $target);
    }

    /**
     * @param Mapping[] $metadata
     */
    private function getMapTarget(array $metadata, mixed $value, object $source, ?object $target): ?Mapping
    {
        $mapTo = null;
        foreach ($metadata as $mapAttribute) {
            if (($if = $mapAttribute->if) && ($fn = $this->getCallable($if, $this->conditionCallableLocator)) && !$this->call($fn, $value, $source, $target)) {
                continue;
            }

            $mapTo = $mapAttribute;
        }

        return $mapTo;
    }

    /**
     * @param (string|callable(mixed $value, object $object): mixed) $fn
     */
    private function getCallable(string|callable $fn, ?ContainerInterface $locator = null): ?callable
    {
        if (\is_callable($fn)) {
            return $fn;
        }

        if ($locator?->has($fn)) {
            return $locator->get($fn);
        }

        return null;
    }
}
