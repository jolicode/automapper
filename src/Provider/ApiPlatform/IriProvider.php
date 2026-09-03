<?php

declare(strict_types=1);

namespace AutoMapper\Provider\ApiPlatform;

use ApiPlatform\Metadata\IriConverterInterface;
use ApiPlatform\Metadata\ResourceClassResolverInterface;
use AutoMapper\MapperContext;
use AutoMapper\Provider\EarlyReturn;
use AutoMapper\Provider\ProviderInterface;

final readonly class IriProvider implements ProviderInterface
{
    /**
     * Formats where a relation is carried as an IRI string, so not only JSON-LD:
     * API Platform's default patch_formats maps application/merge-patch+json to the "json" format.
     */
    private const array SUPPORTED_FORMATS = ['jsonld', 'json', 'jsonhal', 'jsonapi'];

    public function __construct(
        private IriConverterInterface $iriConverter,
        private ResourceClassResolverInterface $resourceClassResolver,
    ) {
    }

    public function provide(string $targetType, mixed $source, array $context, mixed $id): ?object
    {
        if (!\in_array($context[MapperContext::NORMALIZER_FORMAT] ?? null, self::SUPPORTED_FORMATS, true)) {
            return null;
        }

        $isResource = $this->resourceClassResolver->isResourceClass($targetType);

        if (!$isResource) {
            return null;
        }

        if (\is_string($source)) {
            return new EarlyReturn($this->iriConverter->getResourceFromIri($source));
        }

        if (!\is_array($source) || !\array_key_exists('@id', $source) || !\is_string($source['@id'])) {
            return null;
        }

        return $this->iriConverter->getResourceFromIri($source['@id']);
    }
}
