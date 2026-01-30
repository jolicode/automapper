<?php

declare(strict_types=1);

namespace AutoMapper\ObjectMapper;

use AutoMapper\AutoMapper;
use AutoMapper\AutoMapperInterface;
use AutoMapper\MapperContext;
use Psr\Container\ContainerInterface;
use Symfony\Component\ObjectMapper\Exception\MappingException;
use Symfony\Component\ObjectMapper\Exception\MappingTransformException;
use Symfony\Component\ObjectMapper\Metadata\Mapping;
use Symfony\Component\ObjectMapper\Metadata\ObjectMapperMetadataFactoryInterface;
use Symfony\Component\ObjectMapper\Metadata\ReflectionObjectMapperMetadataFactory;
use Symfony\Component\ObjectMapper\ObjectMapperInterface;

final readonly class ObjectMapper implements ObjectMapperInterface
{
    private AutoMapperInterface $autoMapper;

    public function __construct(
        ?AutoMapperInterface $autoMapper = null,
        private ?ContainerInterface $serviceLocator = null,
        private ObjectMapperMetadataFactoryInterface $metadataFactory = new ReflectionObjectMapperMetadataFactory(),
    ) {
        $this->autoMapper = $autoMapper ?? AutoMapper::create();
    }

    public function map(object $source, object|string|null $target = null): object
    {
        $metadata = $this->metadataFactory->create($source);
        $map = $this->getMapTarget($metadata, null, $source, null);

        if (null === $target) {
            /** @var class-string|object $target */
            $target = $map?->target;
        }

        if ($target && $map && $map->transform) {
            // Support only one transform for object mapper at the moment
            $transform = \is_array($map->transform) ? $map->transform[0] : $map->transform;

            if ($fn = $this->getCallable($transform)) {
                $targetRefl = new \ReflectionClass($target);

                $mappedTarget = $this->call($fn, $targetRefl->newInstanceWithoutConstructor(), $source);

                if (!\is_object($mappedTarget)) {
                    throw new MappingTransformException(\sprintf('Cannot map "%s" to a non-object target of type "%s".', get_debug_type($source), get_debug_type($mappedTarget)));
                }

                if (!is_a($mappedTarget, $targetRefl->getName(), false)) {
                    throw new MappingException(\sprintf('Expected the mapped object to be an instance of "%s" but got "%s".', $targetRefl->getName(), get_debug_type($mappedTarget)));
                }

                $target = $mappedTarget;
            }
        }

        if (!$target) {
            throw new MappingException(\sprintf('Mapping target not found for source "%s".', get_debug_type($source)));
        }

        if (\is_string($target) && !class_exists($target)) {
            throw new MappingException(\sprintf('Mapping target class "%s" does not exist for source "%s".', $target, get_debug_type($source)));
        }

        return $this->autoMapper->map($source, $target, [
            MapperContext::SKIP_UNINITIALIZED_VALUES => true,
            MapperContext::INITIALIZE_LAZY_OBJECT => true,
        ]);
    }

    /**
     * @param callable(mixed $value, object $source, ?object $target): mixed $fn
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
            /** @var string|callable(mixed $value, object $source, ?object $target):mixed|null $if */
            $if = $mapAttribute->if;

            if ($if && ($fn = $this->getCallable($if)) && !$this->call($fn, $value, $source, $target)) {
                continue;
            }

            $mapTo = $mapAttribute;
        }

        return $mapTo;
    }

    /**
     * @param (string|callable(mixed $value, object $source, ?object $target): mixed) $fn
     */
    private function getCallable(string|callable $fn): ?callable
    {
        if (\is_callable($fn)) {
            return $fn;
        }

        if ($this->serviceLocator?->has($fn)) {
            /** @var callable(mixed $value, object $source, ?object $target): mixed) */
            return $this->serviceLocator->get($fn);
        }

        return null;
    }
}
