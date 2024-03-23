<?php

declare(strict_types=1);

namespace AutoMapper;

use AutoMapper\Symfony\ExpressionLanguageProvider;
use AutoMapper\Transformer\PropertyTransformer\PropertyTransformerInterface;

/**
 * Class derived for each generated mapper.
 *
 * @author Joel Wurtz <jwurtz@jolicode.com>
 *
 * @template Source of object|array<mixed>
 * @template Target of object|array<mixed>
 *
 * @implements MapperInterface<Source, Target>
 */
abstract class GeneratedMapper implements MapperInterface
{
    /** @var array<string, MapperInterface<object, object>|MapperInterface<object, array<mixed>>|MapperInterface<array<mixed>, object>> */
    protected array $mappers = [];

    /** @var array<string, callable(mixed): void> */
    protected array $hydrateCallbacks = [];

    /** @var array<string, callable(): mixed> */
    protected array $extractCallbacks = [];

    /** @var array<string, callable(): bool>) */
    protected array $extractIsNullCallbacks = [];

    /** @var Target|\ReflectionClass<object> */
    protected mixed $cachedTarget;

    /** @var null|callable(mixed value): mixed */
    protected mixed $circularReferenceHandler = null;

    protected ?int $circularReferenceLimit = null;

    /** @var array<string, PropertyTransformerInterface> */
    protected array $transformers = [];

    protected ?ExpressionLanguageProvider $expressionLanguageProvider = null;

    /**
     * Inject sub mappers.
     */
    public function injectMappers(AutoMapperRegistryInterface $autoMapperRegistry): void
    {
    }

    public function setCircularReferenceHandler(?callable $circularReferenceHandler): void
    {
        $this->circularReferenceHandler = $circularReferenceHandler;
    }

    public function setCircularReferenceLimit(?int $circularReferenceLimit): void
    {
        $this->circularReferenceLimit = $circularReferenceLimit;
    }

    /**
     * @param array<string, PropertyTransformerInterface> $transformers
     */
    public function setPropertyTransformers(array $transformers): void
    {
        $this->transformers = $transformers;
    }

    public function setExpressionLanguageProvider(ExpressionLanguageProvider $expressionLanguageProvider): void
    {
        $this->expressionLanguageProvider = $expressionLanguageProvider;
    }
}
