<?php

declare(strict_types=1);

namespace AutoMapper\Transformer\ApiPlatform;

use ApiPlatform\Metadata\ResourceClassResolverInterface;
use AutoMapper\Metadata\MapperMetadata;
use AutoMapper\Metadata\SourcePropertyMetadata;
use AutoMapper\Metadata\TargetPropertyMetadata;
use AutoMapper\Transformer\ObjectTransformer;
use AutoMapper\Transformer\PrioritizedTransformerFactoryInterface;
use AutoMapper\Transformer\PropertyTransformer\PropertyTransformer;
use AutoMapper\Transformer\TransformerFactoryInterface;
use AutoMapper\Transformer\TransformerInterface;
use Symfony\Component\Serializer\Mapping\Factory\ClassMetadataFactoryInterface;
use Symfony\Component\TypeInfo\Type\ObjectType;
use Symfony\Component\TypeInfo\TypeIdentifier;

final readonly class JsonLdObjectToIdTransformerFactory implements TransformerFactoryInterface, PrioritizedTransformerFactoryInterface
{
    public function __construct(
        private ResourceClassResolverInterface $resourceClassResolver,
        private ClassMetadataFactoryInterface $classMetadataFactory,
    ) {
    }

    public function getTransformer(
        SourcePropertyMetadata $source,
        TargetPropertyMetadata $target,
        MapperMetadata $mapperMetadata,
    ): ?TransformerInterface {
        if (!$source->type instanceof ObjectType || !$target->type) {
            return null;
        }

        if (!$this->resourceClassResolver->isResourceClass($source->type->getClassName())) {
            return null;
        }

        if (!$target->type->isIdentifiedBy(TypeIdentifier::ARRAY) && $target->type->isIdentifiedBy(TypeIdentifier::MIXED)) {
            return null;
        }

        $objectTransformer = new ObjectTransformer($source->type, $target->type);
        $propertyTransformer = new PropertyTransformer(JsonLdIdTransformer::class);
        $serializerClassMetadata = $this->classMetadataFactory->getMetadataFor($source->type->getClassName());
        $groups = [];

        foreach ($serializerClassMetadata->getAttributesMetadata() as $serializerAttributeMetadata) {
            $groupsFound = $serializerAttributeMetadata->getGroups();
            $groups = array_unique([...$groups, ...$groupsFound]);
        }

        return new JsonLdObjectToIdTransformer($objectTransformer, $propertyTransformer, $groups);
    }

    public function getPriority(): int
    {
        return 3;
    }
}
