<?php

declare(strict_types=1);

namespace AutoMapper\EventListener\ApiPlatform;

use ApiPlatform\Api\ResourceClassResolverInterface;
use ApiPlatform\Metadata\HttpOperation;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use AutoMapper\Event\GenerateMapperEvent;
use AutoMapper\Event\PropertyMetadataEvent;
use AutoMapper\Event\SourcePropertyMetadata;
use AutoMapper\Event\TargetPropertyMetadata;
use AutoMapper\Provider\ApiPlatform\IriProvider;
use AutoMapper\Transformer\ApiPlatform\JsonLdContextTransformer;
use AutoMapper\Transformer\ApiPlatform\JsonLdIdTransformer;
use AutoMapper\Transformer\FixedValueTransformer;
use AutoMapper\Transformer\PropertyTransformer\PropertyTransformer;

final readonly class JsonLdListener
{
    public function __construct(
        private ResourceClassResolverInterface $resourceClassResolver,
        private ResourceMetadataCollectionFactoryInterface $resourceMetadataCollectionFactory,
    ) {
    }

    public function __invoke(GenerateMapperEvent $event): void
    {
        if ($event->mapperMetadata->target === 'array' && $this->resourceClassResolver->isResourceClass($event->mapperMetadata->source)) {
            $event->properties['@id'] = new PropertyMetadataEvent(
                mapperMetadata: $event->mapperMetadata,
                source: new SourcePropertyMetadata('@id'),
                target: new TargetPropertyMetadata('@id'),
                transformer: new PropertyTransformer(JsonLdIdTransformer::class),
                if: "(context['normalizer_format'] ?? false) === 'jsonld'",
                disableGroupsCheck: true,
            );

            $operation = $this->resourceMetadataCollectionFactory->create($event->mapperMetadata->source)->getOperation();

            $types = $operation instanceof HttpOperation ? $operation->getTypes() : null;

            if (null === $types) {
                $types = [$operation->getShortName()];
            }

            $fixedTypes = 1 === \count($types) ? $types[0] : $types;

            $event->properties['@type'] = new PropertyMetadataEvent(
                mapperMetadata: $event->mapperMetadata,
                source: new SourcePropertyMetadata('@type'),
                target: new TargetPropertyMetadata('@type'),
                transformer: new FixedValueTransformer($fixedTypes),
                if: "(context['normalizer_format'] ?? false) === 'jsonld'",
                disableGroupsCheck: true,
            );

            $event->properties['@context'] = new PropertyMetadataEvent(
                mapperMetadata: $event->mapperMetadata,
                source: new SourcePropertyMetadata('@context'),
                target: new TargetPropertyMetadata('@context'),
                transformer: new PropertyTransformer(JsonLdContextTransformer::class, ['forced_resource_class' => $event->mapperMetadata->source]),
                if: "(context['normalizer_format'] ?? false) === 'jsonld' and (context['jsonld_has_context'] ?? false) === false and (context['depth'] ?? 0) <= 1",
                disableGroupsCheck: true,
            );
        }

        if ($event->mapperMetadata->source === 'array' && $this->resourceClassResolver->isResourceClass($event->mapperMetadata->target)) {
            $event->provider = IriProvider::class;
        }
    }
}
