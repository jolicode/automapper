<?php

declare(strict_types=1);

namespace AutoMapper\Doctrine\Hydrator;

use AutoMapper\Doctrine\Hydrator\Plan\AliasPlan;
use AutoMapper\Doctrine\Hydrator\Plan\AssociationPlan;
use AutoMapper\Doctrine\Hydrator\Plan\ColumnPlan;
use AutoMapper\Doctrine\Hydrator\Plan\DiscriminatorPlan;
use AutoMapper\Doctrine\Hydrator\Plan\EntityPlan;
use AutoMapper\Doctrine\Hydrator\Plan\HydrationPlan;
use AutoMapper\Doctrine\Hydrator\Plan\IdentifierPart;
use AutoMapper\Doctrine\Hydrator\Plan\PropertyWrite;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Platforms\DB2Platform;
use Doctrine\DBAL\Platforms\OraclePlatform;
use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\AssociationMapping;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\PropertyAccessors\EnumPropertyAccessor;
use Doctrine\ORM\Mapping\PropertyAccessors\ObjectCastPropertyAccessor;
use Doctrine\ORM\Mapping\PropertyAccessors\RawValuePropertyAccessor;
use Doctrine\ORM\Mapping\PropertyAccessors\ReadonlyAccessor;
use Doctrine\ORM\Mapping\PropertyAccessors\TypedNoDefaultPropertyAccessor;
use Doctrine\ORM\Query\ResultSetMapping;

/**
 * Turns a ResultSetMapping into a {@see HydrationPlan}, or rejects it when the generated hydrator cannot
 * reproduce the behavior of the Doctrine hydrator for it.
 *
 * @internal
 */
final class ResultSetMappingAnalyzer
{
    private const DIRECT_ACCESSORS = [
        RawValuePropertyAccessor::class,
        ObjectCastPropertyAccessor::class,
        TypedNoDefaultPropertyAccessor::class,
        ReadonlyAccessor::class,
        EnumPropertyAccessor::class,
    ];

    /** @var array<class-string, int> */
    private array $classes = [];

    /**
     * @throws UnsupportedResultSetMappingException
     */
    public function analyze(ResultSetMapping $rsm, EntityManagerInterface $em, string $mode = HydrationPlan::OBJECT): HydrationPlan
    {
        $this->classes = [];

        if ($rsm->scalarMappings || $rsm->newObjectMappings || $rsm->nestedEntities) {
            throw new UnsupportedResultSetMappingException('scalar or NEW results');
        }

        if (HydrationPlan::SIMPLE === $mode) {
            if (1 !== \count($rsm->aliasMap)) {
                throw new UnsupportedResultSetMappingException('more than one alias for the SimpleObjectHydrator');
            }
        } elseif ($rsm->isMixed) {
            throw new UnsupportedResultSetMappingException('mixed result');
        } elseif (1 !== \count(array_diff_key($rsm->aliasMap, $rsm->parentAliasMap))) {
            throw new UnsupportedResultSetMappingException('more than one root alias');
        }

        $platform = $em->getConnection()->getDatabasePlatform();

        /** @var array<string, ClassMetadata<object>> $metadata */
        $metadata = [];
        /** @var array<class-string, int> $aliasesPerRoot */
        $aliasesPerRoot = [];

        foreach ($rsm->aliasMap as $alias => $className) {
            $class = $metadata[$alias] = $em->getClassMetadata($className);
            $this->register($class);
            $aliasesPerRoot[$class->rootEntityName] = ($aliasesPerRoot[$class->rootEntityName] ?? 0) + 1;
        }

        $fetched = HydrationPlan::OBJECT === $mode ? $this->fetched($rsm, $metadata) : [];
        $aliasIndexes = array_flip(array_keys($rsm->aliasMap));
        $aliases = [];

        foreach ($rsm->aliasMap as $alias => $className) {
            $class = $metadata[$alias];
            $identifier = $this->identifier($class);
            $keyColumns = $this->keyColumns($rsm, $em, $alias);
            $discriminator = $this->discriminator($rsm, $em, $alias, $class, $mode, $platform);

            $parentAlias = $rsm->parentAliasMap[$alias] ?? null;
            $relationKind = AliasPlan::ROOT;
            $relationField = null;
            $parentRelation = null;
            $indexBy = HydrationPlan::OBJECT === $mode ? ($rsm->indexByMap[$alias] ?? null) : null;

            if (null !== $parentAlias) {
                if ($aliasIndexes[$parentAlias] >= $aliasIndexes[$alias]) {
                    throw new UnsupportedResultSetMappingException('child alias declared before its parent');
                }

                $relationField = $rsm->relationMap[$alias];
                $parentRelation = $metadata[$parentAlias]->associationMappings[$relationField];
                $relationKind = $parentRelation->isToOne() ? AliasPlan::TO_ONE : AliasPlan::TO_MANY;
            }

            $fetchedCollections = [];

            foreach ($rsm->parentAliasMap as $child => $parent) {
                $field = $rsm->relationMap[$child];

                if ($parent === $alias && $class->associationMappings[$field]->isToMany()) {
                    $fetchedCollections[] = $field;
                }
            }

            $concreteClasses = null === $discriminator
                ? [$class]
                : array_map(fn (int $index) => $em->getClassMetadata($this->className($index)), array_values(array_unique($discriminator->map)));

            $entities = [];

            foreach ($concreteClasses as $concrete) {
                $entity = $this->entity($rsm, $em, $alias, $concrete, $discriminator, $fetched[$alias] ?? [], $fetchedCollections, $parentRelation, $indexBy);

                foreach ($identifier as $part) {
                    $column = null;

                    foreach ($entity->columns as $candidate) {
                        if ($candidate->key === $part->key) {
                            $column = $candidate;
                        }
                    }

                    if (null === $column) {
                        if (is_a($concrete->name, $class->name, true)) {
                            throw new UnsupportedResultSetMappingException(\sprintf('identifier "%s" of alias "%s" is not selected', $part->field, $alias));
                        }

                        // a class above the queried one: its fields are filtered out, only the unit of work can deal with such a row
                        $entity = new EntityPlan($entity->classIndex, $entity->className, $entity->columns, $entity->associations, $entity->backReference, $entity->backReferenceField, 'class outside of the queried hierarchy', $entity->cloneable);

                        continue;
                    }

                    if (null !== $column->enumType || \in_array($column->type, ['binary', 'blob'], true)) {
                        throw new UnsupportedResultSetMappingException('identifier type');
                    }
                }

                $entities[$entity->classIndex] = $entity;
            }

            $aliases[] = new AliasPlan(
                alias: $alias,
                index: $aliasIndexes[$alias],
                className: $class->name,
                classIndex: $this->register($class),
                rootEntityName: $class->rootEntityName,
                relationKind: $relationKind,
                parentIndex: null === $parentAlias ? null : $aliasIndexes[$parentAlias],
                relationField: $relationField,
                indexByColumn: $indexBy,
                keyColumns: $keyColumns,
                identifier: $identifier,
                discriminator: $discriminator,
                entities: $entities,
                fetchedCollections: $fetchedCollections,
                sharedCollections: $aliasesPerRoot[$class->rootEntityName] > 1,
            );
        }

        return new HydrationPlan($mode, $aliases, array_keys($this->classes), $fetched, $platform::class);
    }

    /**
     * @param ClassMetadata<object> $class
     */
    private function register(ClassMetadata $class): int
    {
        return $this->classes[$class->name] ??= \count($this->classes);
    }

    /**
     * @return class-string
     */
    private function className(int $index): string
    {
        /** @var class-string $className */
        $className = array_search($index, $this->classes, true);

        return $className;
    }

    /**
     * @param ClassMetadata<object> $class
     *
     * @return list<IdentifierPart>
     */
    private function identifier(ClassMetadata $class): array
    {
        $parts = [];

        foreach ($class->identifier as $field) {
            if (!isset($class->associationMappings[$field])) {
                $parts[] = new IdentifierPart($field, $field, false);

                continue;
            }

            $assoc = $class->associationMappings[$field];

            if (!$assoc->isToOneOwningSide() || 1 !== \count($assoc->joinColumns)) {
                throw new UnsupportedResultSetMappingException(\sprintf('identifier association "%s" with several join columns', $field));
            }

            $parts[] = new IdentifierPart($field, $assoc->joinColumns[0]->name, true);
        }

        return $parts;
    }

    /**
     * Identifier columns of the alias, like the ones AbstractHydrator::gatherRowData() builds the id of a row from.
     *
     * @return list<string>
     */
    private function keyColumns(ResultSetMapping $rsm, EntityManagerInterface $em, string $alias): array
    {
        $columns = [];

        foreach ($rsm->columnOwnerMap as $column => $owner) {
            if ($owner !== $alias) {
                continue;
            }

            if (isset($rsm->fieldMappings[$column])) {
                if (\in_array($rsm->fieldMappings[$column], $em->getClassMetadata($rsm->declaringClasses[$column])->identifier, true)) {
                    $columns[] = (string) $column;
                }
            } elseif (isset($rsm->metaMappings[$column], $rsm->isIdentifierColumn[$alias][$column])) {
                $columns[] = (string) $column;
            }
        }

        if (!$columns) {
            throw new UnsupportedResultSetMappingException(\sprintf('identifier of alias "%s" is not selected', $alias));
        }

        return $columns;
    }

    /**
     * @param ClassMetadata<object> $class
     */
    private function discriminator(ResultSetMapping $rsm, EntityManagerInterface $em, string $alias, ClassMetadata $class, string $mode, AbstractPlatform $platform): ?DiscriminatorPlan
    {
        if (HydrationPlan::OBJECT === $mode) {
            if (!isset($rsm->discriminatorColumns[$alias])) {
                return null;
            }

            $column = $rsm->discriminatorColumns[$alias];

            if (!isset($rsm->metaMappings[$column])) {
                throw new UnsupportedResultSetMappingException('discriminator column is not selected');
            }

            return new DiscriminatorPlan($column, $rsm->typeMappings[$column] ?? null, self::enumType($rsm, $column), $this->discriminatorMap($em, $class), $rsm->metaMappings[$column]);
        }

        if ($class->isInheritanceTypeNone()) {
            return null;
        }

        // same lookup as SimpleObjectHydrator::hydrateRowData()
        $name = $class->getDiscriminatorColumn()->name;
        $name = match (true) {
            $platform instanceof DB2Platform, $platform instanceof OraclePlatform => strtoupper($name),
            $platform instanceof PostgreSQLPlatform => strtolower($name),
            default => $name,
        };
        $column = array_search($name, $rsm->metaMappings, true) ?: $name;

        return new DiscriminatorPlan((string) $column, null, null, $this->discriminatorMap($em, $class), (string) $column);
    }

    /**
     * @param ClassMetadata<object> $class
     *
     * @return array<string, int>
     */
    private function discriminatorMap(EntityManagerInterface $em, ClassMetadata $class): array
    {
        $map = [];

        foreach ($class->discriminatorMap as $value => $className) {
            $map[(string) $value] = $this->register($em->getClassMetadata($className));
        }

        return $map;
    }

    /**
     * @param ClassMetadata<object> $concrete
     * @param array<string, true>   $fetched
     * @param list<string>          $fetchedCollections
     */
    private function entity(ResultSetMapping $rsm, EntityManagerInterface $em, string $alias, ClassMetadata $concrete, ?DiscriminatorPlan $discriminator, array $fetched, array $fetchedCollections, ?AssociationMapping $parentRelation, ?string $indexBy): EntityPlan
    {
        $classIndex = $this->register($concrete);
        // fields declared in a subclass only belong to the rows of that subclass, see AbstractHydrator::hydrateColumnInfo()
        $filterSubclassFields = isset($rsm->discriminatorColumns[$alias]);
        $columns = [];
        $keys = [];

        foreach ($rsm->columnOwnerMap as $column => $owner) {
            $column = (string) $column;

            if ($owner !== $alias || $column === $discriminator?->column) {
                continue;
            }

            if (isset($rsm->fieldMappings[$column])) {
                $field = $rsm->fieldMappings[$column];
                $declaring = $em->getClassMetadata($rsm->declaringClasses[$column]);

                if ($filterSubclassFields && $declaring->parentClasses && !is_a($concrete->name, $declaring->name, true)) {
                    continue;
                }

                $enumType = self::enumType($rsm, $column);
                $write = null;

                if (isset($concrete->fieldMappings[$field])) {
                    $write = $this->write($concrete, $field);

                    if (null !== $concrete->fieldMappings[$field]->enumType && null === $enumType) {
                        // the enum is then built by the Doctrine accessor, with its own conversion rules
                        $write = new PropertyWrite($write->property, false, $write->unsetOnNull, $write->hasDefault);
                    }
                }

                $columns[] = new ColumnPlan($column, $field, $declaring->fieldMappings[$field]->type, $enumType, $write);
                $key = $field;
            } elseif (isset($rsm->metaMappings[$column])) {
                $key = $rsm->metaMappings[$column];
                $columns[] = new ColumnPlan($column, $key, $rsm->typeMappings[$column] ?? null, self::enumType($rsm, $column));
            } else {
                continue;
            }

            if (isset($keys[$key])) {
                throw new UnsupportedResultSetMappingException(\sprintf('column collision on "%s"', $key));
            }

            $keys[$key] = true;
        }

        $slowReason = null;
        $associations = [];

        foreach ($concrete->associationMappings as $field => $assoc) {
            if (isset($fetched[$field])) {
                if (\in_array($field, $fetchedCollections, true)) {
                    $associations[] = new AssociationPlan(AssociationPlan::FETCHED_COLLECTION, $field, $classIndex, $this->register($em->getClassMetadata($assoc->targetEntity)), $this->write($concrete, $field));
                }

                continue;
            }

            if ($assoc->isToMany()) {
                $associations[] = new AssociationPlan(AssociationPlan::LAZY_COLLECTION, $field, $classIndex, $this->register($em->getClassMetadata($assoc->targetEntity)), $this->write($concrete, $field));

                continue;
            }

            if (!$assoc->isOwningSide()) {
                $slowReason = \sprintf('inverse side to-one "%s" is loaded by the unit of work', $field);

                continue;
            }

            if ($assoc->isOneToOne() && null !== $assoc->inversedBy) {
                $slowReason = \sprintf('reference "%s" has an inverse one-to-one side', $field);

                continue;
            }

            $target = $em->getClassMetadata($assoc->targetEntity);
            $foreignKeys = [];

            foreach ($assoc->targetToSourceKeyColumns as $targetColumn => $sourceColumn) {
                $targetField = $target->fieldNames[$targetColumn] ?? null;

                if (null === $targetField || !\in_array($targetField, $target->identifier, true)) {
                    break;
                }

                $foreignKeys[$targetField] = (string) $sourceColumn;
            }

            if ($target->subClasses || $target->containsForeignIdentifier || \count($foreignKeys) !== \count($target->identifier) || \count($foreignKeys) !== \count($assoc->targetToSourceKeyColumns)) {
                $slowReason = \sprintf('reference "%s" cannot be built from the foreign key alone', $field);

                continue;
            }

            $associations[] = new AssociationPlan(AssociationPlan::REFERENCE, $field, $classIndex, $this->register($target), $this->write($concrete, $field), $foreignKeys);
        }

        $backReference = null;
        $backReferenceField = null;

        if (null !== $parentRelation && $parentRelation->isOneToMany() && null === $indexBy) {
            $backReferenceField = $parentRelation->mappedBy;
            $backReference = $this->write($concrete, $backReferenceField);
        }

        $reflection = $concrete->getReflectionClass();

        return new EntityPlan(
            classIndex: $classIndex,
            className: $concrete->name,
            columns: $columns,
            associations: $associations,
            backReference: $backReference,
            backReferenceField: $backReferenceField,
            slowReason: $slowReason,
            cloneable: !$reflection->isAbstract() && $reflection->isCloneable() && !$reflection->hasMethod('__clone'),
        );
    }

    /**
     * Same computation as ObjectHydrator::prepare(): associations filled by the query itself.
     *
     * @param array<string, ClassMetadata<object>> $metadata
     *
     * @return array<string, array<string, true>>
     */
    private function fetched(ResultSetMapping $rsm, array $metadata): array
    {
        $fetched = [];

        foreach ($rsm->aliasMap as $alias => $className) {
            if (!isset($rsm->relationMap[$alias])) {
                continue;
            }

            $parent = $rsm->parentAliasMap[$alias];

            if (!isset($metadata[$parent])) {
                throw new UnsupportedResultSetMappingException('parent alias not selected');
            }

            $assoc = $metadata[$parent]->associationMappings[$rsm->relationMap[$alias]];
            $fetched[$parent][$assoc->fieldName] = true;

            if ($assoc->isManyToMany()) {
                continue;
            }

            if (!$assoc->isOwningSide()) {
                $fetched[$alias][$assoc->mappedBy] = true;

                continue;
            }

            if (null !== $assoc->inversedBy) {
                $inverseAssoc = $metadata[$alias]->associationMappings[$assoc->inversedBy];

                if ($inverseAssoc->isToOne()) {
                    $fetched[$alias][$inverseAssoc->fieldName] = true;
                }
            }
        }

        return $fetched;
    }

    /**
     * @return class-string<\BackedEnum>|null
     */
    private static function enumType(ResultSetMapping $rsm, string $column): ?string
    {
        /** @var array<string, class-string<\BackedEnum>> $enumMappings */
        $enumMappings = $rsm->enumMappings;

        return $enumMappings[$column] ?? null;
    }

    /**
     * @param ClassMetadata<object> $class
     */
    private function write(ClassMetadata $class, string $field): PropertyWrite
    {
        $accessor = $class->propertyAccessors[$field];
        $reflection = $accessor->getUnderlyingReflector();
        $type = $reflection->getType();

        $direct = \in_array($accessor::class, self::DIRECT_ACCESSORS, true)
            && !isset($class->fieldMappings[$field]->declaredField)
            && !$reflection->isStatic()
            && !$reflection->hasHooks()
            && (
                $reflection->getDeclaringClass()->name === $class->name
                || (!$reflection->isPrivate() && !$reflection->isReadOnly() && !$reflection->isPrivateSet())
            );

        return new PropertyWrite(
            $reflection->getName(),
            $direct,
            null !== $type && !$type->allowsNull(),
            $reflection->hasDefaultValue(),
        );
    }
}
