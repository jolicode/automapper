<?php

declare(strict_types=1);

namespace AutoMapper\Normalizer;

use AutoMapper\AutoMapperInterface;
use AutoMapper\AutoMapperRegistryInterface;
use AutoMapper\MapperContext;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\Normalizer\DateTimeNormalizer;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

/**
 * Bridge for symfony/serializer.
 *
 * @author Joel Wurtz <jwurtz@jolicode.com>
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
        private AutoMapperInterface&AutoMapperRegistryInterface $autoMapper,
    ) {
    }

    public function normalize(mixed $object, string $format = null, array $context = []): array|string|int|float|bool|\ArrayObject|null
    {
        return $this->autoMapper->map($object, 'array', $this->createAutoMapperContext($context));
    }

    public function denormalize(mixed $data, string $type, string $format = null, array $context = []): mixed
    {
        return $this->autoMapper->map($data, $type, $this->createAutoMapperContext($context));
    }

    public function supportsNormalization(mixed $data, string $format = null, array $context = []): bool
    {
        if (!\is_object($data) || $data instanceof \stdClass) {
            return false;
        }

        return $this->autoMapper->hasMapper($data::class, 'array');
    }

    public function supportsDenormalization(mixed $data, string $type, string $format = null, array $context = []): bool
    {
        return $this->autoMapper->hasMapper('array', $type);
    }

    public function getSupportedTypes(?string $format): array
    {
        return ['object' => true];
    }

    private function createAutoMapperContext(array $serializerContext = []): array
    {
        $context = [];

        foreach (self::SERIALIZER_CONTEXT_MAPPING as $serializerContextName => $autoMapperContextName) {
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

        return $context + $serializerContext;
    }
}
