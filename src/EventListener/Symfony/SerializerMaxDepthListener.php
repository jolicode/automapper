<?php

declare(strict_types=1);

namespace AutoMapper\EventListener\Symfony;

use AutoMapper\Event\PropertyMetadataEvent;
use Symfony\Component\Serializer\Mapping\Factory\ClassMetadataFactory;

final readonly class SerializerMaxDepthListener
{
    public function __construct(private ClassMetadataFactory $classMetadataFactory)
    {
    }

    public function __invoke(PropertyMetadataEvent $event): void
    {
        $targetMaxDepth = $this->getMaxDepth($event->mapperMetadata->target, $event->target->name);
        $sourceMaxDepth = $this->getMaxDepth($event->mapperMetadata->source, $event->source->name);

        // Extract the property metadata
        if ($targetMaxDepth !== null || $sourceMaxDepth !== null) {
            $event->maxDepth = match (true) {
                null !== $sourceMaxDepth && null !== $targetMaxDepth => min($sourceMaxDepth, $targetMaxDepth),
                null !== $sourceMaxDepth => $sourceMaxDepth,
                null !== $targetMaxDepth => $targetMaxDepth,
            };
        }
    }

    private function getMaxDepth(string $class, string $property): ?int
    {
        if ('array' === $class || \stdClass::class === $class) {
            return null;
        }

        $serializerClassMetadata = $this->classMetadataFactory->getMetadataFor($class);
        $maxDepth = null;

        foreach ($serializerClassMetadata->getAttributesMetadata() as $serializerAttributeMetadata) {
            if ($serializerAttributeMetadata->getName() === $property) {
                $maxDepth = $serializerAttributeMetadata->getMaxDepth();
            }
        }

        return $maxDepth;
    }
}
