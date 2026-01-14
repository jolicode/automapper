<?php

declare(strict_types=1);

namespace AutoMapper\Transformer\ApiPlatform;

use ApiPlatform\JsonLd\AnonymousContextBuilderInterface;
use ApiPlatform\JsonLd\ContextBuilderInterface;
use ApiPlatform\Metadata\ResourceClassResolverInterface;
use AutoMapper\Transformer\PropertyTransformer\PropertyTransformerInterface;

final readonly class JsonLdContextTransformer implements PropertyTransformerInterface
{
    public function __construct(
        private ContextBuilderInterface $contextBuilder,
        private ResourceClassResolverInterface $resourceClassResolver,
    ) {
    }

    public function transform(mixed $value, object|array $source, array $context): mixed
    {
        if (!\is_object($source)) {
            return null;
        }

        $resourceClass = $context['forced_resource_class'] ?? $this->resourceClassResolver->isResourceClass($source::class) ? $this->resourceClassResolver->getResourceClass($source) : null;

        if (null === $resourceClass) {
            if ($this->contextBuilder instanceof AnonymousContextBuilderInterface) {
                return $this->contextBuilder->getAnonymousResourceContext($source, ($context['output'] ?? []) + ['api_resource' => $context['api_resource'] ?? null]);
            }

            return null;
        }

        if (isset($context['jsonld_embed_context'])) {
            return $this->contextBuilder->getResourceContext($resourceClass);
        }

        return $this->contextBuilder->getResourceContextUri($resourceClass);
    }
}
