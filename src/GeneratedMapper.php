<?php

declare(strict_types=1);

namespace AutoMapper;

use AutoMapper\Provider\ProviderRegistry;
use AutoMapper\Symfony\ExpressionLanguageProvider;
use AutoMapper\Transformer\PropertyTransformer\PropertyTransformerRegistry;

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
    final public function __construct(
        protected PropertyTransformerRegistry $transformerRegistry,
        protected ProviderRegistry $providerRegistry,
        protected ?ExpressionLanguageProvider $expressionLanguageProvider = null,
    ) {
        $this->initialize();
    }

    public function initialize(): void
    {
    }

    public function registerMappers(AutoMapperRegistryInterface $registry): void
    {
    }

    public function getSourceHash(mixed $value): ?string
    {
        return null;
    }

    public function getTargetHash(mixed $value): ?string
    {
        return null;
    }

    /** @var array<string, MapperInterface<object, object>|MapperInterface<object, array<mixed>>|MapperInterface<array<mixed>, object>> */
    protected array $mappers = [];

    /** @var array<string, callable(mixed): void> */
    protected array $hydrateCallbacks = [];

    /** @var array<string, callable(): mixed> */
    protected array $extractCallbacks = [];

    /** @var array<string, callable(): bool>) */
    protected array $extractIsNullCallbacks = [];

    /** @var array<string, callable(): bool>) */
    protected array $extractIsUndefinedCallbacks = [];

    /** @var array<string, callable(): mixed> */
    protected array $extractTargetCallbacks = [];

    /** @var array<string, callable(): bool>) */
    protected array $extractTargetIsNullCallbacks = [];

    /** @var array<string, callable(): bool>) */
    protected array $extractTargetIsUndefinedCallbacks = [];

    /** @var Target|\ReflectionClass<object> */
    protected mixed $cachedTarget;
}
