<?php

declare(strict_types=1);

namespace AutoMapper;

use AutoMapper\Transformer\CustomTransformer\CustomTransformerInterface;

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
    protected $mappers = [];

    protected $callbacks;

    protected $hydrateCallbacks = [];

    protected $extractCallbacks = [];

    /** @var callable[] */
    protected array $extractIsNullCallbacks = [];

    protected $cachedTarget;

    protected $circularReferenceHandler;

    protected $circularReferenceLimit;

    /** @var array<string, CustomTransformerInterface> */
    protected array $transformers = [];

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
     * @param array<string, CustomTransformerInterface> $transformers
     */
    public function setCustomTransformers(array $transformers): void
    {
        $this->transformers = $transformers;
    }
}
