<?php

declare(strict_types=1);

namespace AutoMapper\EventListener\Symfony;

use AutoMapper\Event\PropertyMetadataEvent;
use Symfony\Component\Serializer\Mapping\Factory\ClassMetadataFactory;

final readonly class SerializerGroupListener
{
    public function __construct(
        private ClassMetadataFactory $classMetadataFactory
    ) {
    }

    public function __invoke(PropertyMetadataEvent $event): void
    {
        $event->target->groups = $this->getGroups($event->mapperMetadata->target, $event->target->name);
        $event->source->groups = $this->getGroups($event->mapperMetadata->source, $event->source->name);
    }

    /**
     * @return string[]|null
     */
    private function getGroups(string $class, string $property): ?array
    {
        if ('array' === $class || \stdClass::class === $class) {
            return null;
        }

        $serializerClassMetadata = $this->classMetadataFactory->getMetadataFor($class);
        $anyGroupFound = false;
        $groups = [];

        foreach ($serializerClassMetadata->getAttributesMetadata() as $serializerAttributeMetadata) {
            $groupsFound = $serializerAttributeMetadata->getGroups();

            if ($groupsFound) {
                $anyGroupFound = true;
            }

            if ($serializerAttributeMetadata->getName() === $property) {
                $groups = $groupsFound;
            }
        }

        if (!$anyGroupFound) {
            return null;
        }

        return $groups;
    }
}
