<?php

declare(strict_types=1);

namespace AutoMapper\Provider\ApiPlatform;

use ApiPlatform\Api\IriConverterInterface;
use ApiPlatform\Api\ResourceClassResolverInterface;
use AutoMapper\MapperContext;
use AutoMapper\Provider\EarlyReturn;
use AutoMapper\Provider\ProviderInterface;

final readonly class IriProvider implements ProviderInterface
{
    public function __construct(
        private IriConverterInterface $iriConverter,
        private ResourceClassResolverInterface $resourceClassResolver
    ) {
    }

    public function provide(string $targetType, mixed $source, array $context): object|array|null
    {
        if (($context[MapperContext::NORMALIZER_FORMAT] ?? false) !== 'jsonld') {
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
