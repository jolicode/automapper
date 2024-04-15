<?php

declare(strict_types=1);

namespace AutoMapper\Provider;

use AutoMapper\Exception\InvalidArgumentException;

/**
 * @internal
 */
final readonly class ProviderRegistry
{
    /** @var array<class-string, ProviderInterface> */
    private array $providers;

    /**
     * @param iterable<class-string|int, ProviderInterface> $providers
     */
    public function __construct(iterable $providers)
    {
        /** @var array<class-string, ProviderInterface> $indexedProviders */
        $indexedProviders = [];

        foreach ($providers as $key => $provider) {
            if (\is_int($key)) {
                $key = $provider::class;
            }

            $indexedProviders[$key] = $provider;
        }

        $this->providers = $indexedProviders;
    }

    public function getProvider(string $id): ProviderInterface
    {
        if (!\array_key_exists($id, $this->providers)) {
            throw new InvalidArgumentException(sprintf('Provider with id "%s" not found.', $id));
        }

        return $this->providers[$id];
    }
}
