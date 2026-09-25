<?php

declare(strict_types=1);

namespace AutoMapper\Doctrine\Hydrator\Plan;

/**
 * Everything needed to hydrate one DQL alias of the result set.
 *
 * @internal
 */
final readonly class AliasPlan
{
    public const ROOT = 'root';
    public const TO_MANY = 'to_many';
    public const TO_ONE = 'to_one';

    public function __construct(
        public string $alias,
        public int $index,
        /** @var class-string */
        public string $className,
        /** Index of the class of the alias itself, which may be abstract in an inheritance hierarchy */
        public int $classIndex,
        /** @var class-string */
        public string $rootEntityName,
        public string $relationKind,
        public ?int $parentIndex,
        public ?string $relationField,
        public ?string $indexByColumn,
        /** @var list<string> identifier columns, their non null values make the key of the entity in the result set */
        public array $keyColumns,
        /** @var list<IdentifierPart> in the order of ClassMetadata::$identifier */
        public array $identifier,
        public ?DiscriminatorPlan $discriminator,
        /** @var array<int, EntityPlan> class index => plan */
        public array $entities,
        /** @var list<string> to-many fields filled by child aliases */
        public array $fetchedCollections,
        /** The same class is hydrated by several aliases, so collection state must be shared by object */
        public bool $sharedCollections,
    ) {
    }

    /**
     * A single scalar identifier: hashed and registered as its converted value, the common case.
     */
    public function hasSimpleIdentifier(): bool
    {
        return 1 === \count($this->identifier) && !$this->identifier[0]->association;
    }

    /**
     * The concrete class is resolved from the discriminator of each row.
     */
    public function isPolymorphic(): bool
    {
        return null !== $this->discriminator;
    }

    public function firstEntity(): EntityPlan
    {
        return array_values($this->entities)[0];
    }

    /**
     * Identifier columns are the same for every class of the hierarchy that selects them.
     */
    public function identifierColumn(string $key): ColumnPlan
    {
        foreach ($this->entities as $entity) {
            foreach ($entity->columns as $column) {
                if ($column->key === $key) {
                    return $column;
                }
            }
        }

        throw new \LogicException(\sprintf('No column for identifier "%s" of alias "%s".', $key, $this->alias));
    }
}
