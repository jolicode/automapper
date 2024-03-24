<?php

declare(strict_types=1);

namespace AutoMapper\Normalizer;

use AutoMapper\AutoMapperInterface;
use AutoMapper\MapperContext;
use AutoMapper\Metadata\MetadataRegistry;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\Normalizer\DateTimeNormalizer;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

/**
 * Bridge for symfony/serializer.
 *
 * @author Joel Wurtz <jwurtz@jolicode.com>
 *
 * @phpstan-import-type MapperContextArray from MapperContext
 */
readonly class AutoMapperNormalizer implements NormalizerInterface, DenormalizerInterface
{
    private const SERIALIZER_CONTEXT_MAPPING = [
        AbstractNormalizer::GROUPS => MapperContext::GROUPS,
        AbstractNormalizer::ATTRIBUTES => MapperContext::ALLOWED_ATTRIBUTES,
        AbstractNormalizer::IGNORED_ATTRIBUTES => MapperContext::IGNORED_ATTRIBUTES,
        AbstractNormalizer::OBJECT_TO_POPULATE => MapperContext::TARGET_TO_POPULATE,
        AbstractNormalizer::CIRCULAR_REFERENCE_LIMIT => MapperContext::CIRCULAR_REFERENCE_LIMIT,
        AbstractNormalizer::CIRCULAR_REFERENCE_HANDLER => MapperContext::CIRCULAR_REFERENCE_HANDLER,
        DateTimeNormalizer::FORMAT_KEY => MapperContext::DATETIME_FORMAT,
    ];

    public function __construct(
        private AutoMapperInterface $autoMapper,
        private ?MetadataRegistry $onlyMetadataRegistry = null,
    ) {
    }

    /**
     * @param object               $object
     * @param array<string, mixed> $context
     *
     * @return array<string, mixed>
     */
    public function normalize(mixed $object, string $format = null, array $context = []): ?array
    {
        return $this->autoMapper->map($object, 'array', $this->createAutoMapperContext($format, $context));
    }

    /**
     * @template T of object
     *
     * @param array<string, mixed> $data
     * @param class-string<T>      $type
     * @param array<string, mixed> $context
     *
     * @return T|null
     */
    public function denormalize(mixed $data, string $type, string $format = null, array $context = []): mixed
    {
        return $this->autoMapper->map($data, $type, $this->createAutoMapperContext($format, $context));
    }

    /**
     * @param array<string, mixed> $context
     */
    public function supportsNormalization(mixed $data, string $format = null, array $context = []): bool
    {
        if (!\is_object($data) || $data instanceof \stdClass) {
            return false;
        }

        if (is_iterable($data)) {
            return false;
        }

        if ($this->onlyMetadataRegistry === null) {
            return true;
        }

        return $this->onlyMetadataRegistry->has($data::class, 'array');
    }

    /**
     * @param array<string, mixed> $context
     */
    public function supportsDenormalization(mixed $data, string $type, string $format = null, array $context = []): bool
    {
        if (!class_exists($type)) {
            return false;
        }

        if ($this->onlyMetadataRegistry === null) {
            return true;
        }

        return $this->onlyMetadataRegistry->has('array', $type);
    }

    public function getSupportedTypes(?string $format): array
    {
        if ($this->onlyMetadataRegistry === null) {
            return ['object' => true];
        }

        $types = [];

        foreach ($this->onlyMetadataRegistry as $metadata) {
            if ($metadata->source === 'array') {
                $hasTarget = $this->onlyMetadataRegistry->has($metadata->target, 'array');

                // Only cache when both source and target exist in the registry
                $types[$metadata->target] = $hasTarget;
            } elseif ($metadata->target === 'array') {
                $hasSource = $this->onlyMetadataRegistry->has($metadata->target, 'array');

                // Only cache when both source and target exist in the registry
                $types[$metadata->source] = $hasSource;
            }
        }

        return $types;
    }

    /**
     * @param array<string, mixed> $serializerContext
     *
     * @return MapperContextArray
     */
    private function createAutoMapperContext(string $format = null, array $serializerContext = []): array
    {
        /** @var MapperContextArray $context */
        $context = [];

        foreach (self::SERIALIZER_CONTEXT_MAPPING as $serializerContextName => $autoMapperContextName) {
            if (!\array_key_exists($serializerContextName, $serializerContext)) {
                continue;
            }

            $context[$autoMapperContextName] = $serializerContext[$serializerContextName] ?? null;
            unset($serializerContext[$serializerContextName]);
        }

        if (\array_key_exists(AbstractNormalizer::DEFAULT_CONSTRUCTOR_ARGUMENTS, $serializerContext) && is_iterable($serializerContext[AbstractNormalizer::DEFAULT_CONSTRUCTOR_ARGUMENTS])) {
            foreach ($serializerContext[AbstractNormalizer::DEFAULT_CONSTRUCTOR_ARGUMENTS] as $class => $keyArgs) {
                foreach ($keyArgs as $key => $value) {
                    $context[MapperContext::CONSTRUCTOR_ARGUMENTS][$class][$key] = $value;
                }
            }

            unset($serializerContext[AbstractNormalizer::DEFAULT_CONSTRUCTOR_ARGUMENTS]);
        }

        if (\array_key_exists(MapperContext::TARGET_TO_POPULATE, $context)) {
            if (!\is_object($context[MapperContext::TARGET_TO_POPULATE]) && !\is_array($context[MapperContext::TARGET_TO_POPULATE])) {
                unset($context[MapperContext::TARGET_TO_POPULATE]);
            }
        }

        if ($format !== null) {
            $context[MapperContext::NORMALIZER_FORMAT] = $format;
        }

        /** @var MapperContextArray */
        return $context + $serializerContext;
    }
}
