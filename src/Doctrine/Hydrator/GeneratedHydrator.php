<?php

declare(strict_types=1);

namespace AutoMapper\Doctrine\Hydrator;

use AutoMapper\Doctrine\Hydrator\Plan\HydrationPlan;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Result;
use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\ListenersInvoker;
use Doctrine\ORM\Event\PostLoadEventArgs;
use Doctrine\ORM\Events;
use Doctrine\ORM\Internal\Hydration\HydrationException;
use Doctrine\ORM\Mapping\AssociationMapping;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\MappingException;
use Doctrine\ORM\Mapping\PropertyAccessors\PropertyAccessor;
use Doctrine\ORM\PersistentCollection;
use Doctrine\ORM\Query;
use Doctrine\ORM\UnitOfWork;

/**
 * Base class of the hydrators generated for a given result set mapping.
 *
 * The generated code handles the common path (entities that are not yet managed) inline, and falls back
 * on the helpers of this class, which replicate the Doctrine hydrators, for everything else.
 *
 * Public members are used by the closures bound to the entity classes.
 */
abstract class GeneratedHydrator
{
    protected const MODE = HydrationPlan::OBJECT;
    /** @var list<class-string> */
    protected const CLASSES = [];
    /** @var list<string> DBAL types that are not converted inline */
    protected const TYPES = [];
    /** @var array<int, array{string, list<int>}> alias index => [dql alias, index of each concrete class] */
    protected const ALIASES = [];
    /** @var array<string, array<string, true>> */
    protected const FETCHED = [];
    /** @var array<int, array<int, string>> alias index => class index => why the unit of work must create its entities */
    protected const SLOW = [];
    /** @var array<int, array{map: array<array-key, int>, class: class-string, name: string, alias: string}> */
    protected const DISCRIMINATORS = [];
    /** @var list<array{int, string}> [class index, field] */
    protected const ASSOCIATIONS = [];
    /** @var list<array{int, string}> [class index, field] */
    protected const ACCESSORS = [];

    public readonly UnitOfWork $uow;
    public readonly AbstractPlatform $platform;

    /** @var array<int, ClassMetadata<object>> */
    public array $classes = [];
    /** @var array<string, Type> */
    public array $types = [];
    /** @var list<AssociationMapping> */
    public array $associations = [];
    /** @var list<PropertyAccessor> */
    public array $accessors = [];

    /** @var array<int, object> class index => instance to clone */
    protected array $prototypes = [];
    /** @var array<int, array<int, \Closure>> alias index => class index => closure writing a new entity from a row */
    protected array $fills = [];
    /** @var array<int, array<int, \Closure>> alias index => class index => closure extracting the entity data from a row */
    protected array $gathers = [];

    /** Set by an onClear event during the hydration: entities seen so far are not managed anymore */
    protected bool $cleared = false;

    private readonly ListenersInvoker $listenersInvoker;

    /** @var array{slow: array<int, array<int, bool>>, postLoadFlags: array<int, int<0, 7>>, readOnly: bool}|null */
    private ?array $iteration = null;

    /** @var array<class-string<\BackedEnum>, bool> */
    private array $intBackedEnums = [];

    final public function __construct(
        public readonly EntityManagerInterface $em,
    ) {
        $this->uow = $em->getUnitOfWork();
        $this->platform = $em->getConnection()->getDatabasePlatform();
        $this->listenersInvoker = new ListenersInvoker($em);

        foreach (static::CLASSES as $index => $className) {
            $class = $this->classes[$index] = $em->getClassMetadata($className);
            $reflection = $class->getReflectionClass();

            if (!$reflection->isAbstract() && $reflection->isCloneable() && !$reflection->hasMethod('__clone')) {
                $this->prototypes[$index] = $reflection->newInstanceWithoutConstructor();
            }
        }

        foreach (static::TYPES as $type) {
            $this->types[$type] = Type::getType($type);
        }

        foreach (static::ASSOCIATIONS as [$classIndex, $field]) {
            $this->associations[] = $this->classes[$classIndex]->associationMappings[$field];
        }

        foreach (static::ACCESSORS as [$classIndex, $field]) {
            $this->accessors[] = $this->classes[$classIndex]->propertyAccessors[$field];
        }

        $this->initialize();
    }

    /**
     * Hydrates all the rows of the statement, like AbstractHydrator::hydrateAll().
     *
     * @param array<string, mixed> $hints
     *
     * @return mixed[]
     */
    final public function hydrate(Result $stmt, array $hints): array
    {
        [$slow, $postLoadFlags, $readOnly] = $this->prepare($hints);
        $postLoad = [];
        $eventManager = $this->em->getEventManager();
        $eventManager->addEventListener([Events::onClear], $this);
        $this->cleared = false;

        try {
            $result = $this->doHydrate($stmt, $hints, $slow, $postLoadFlags, $readOnly, $postLoad);
        } finally {
            $stmt->free();
            $eventManager->removeEventListener([Events::onClear], $this);
        }

        if (HydrationPlan::SIMPLE === static::MODE || true === $hints[UnitOfWork::HINT_DEFEREAGERLOAD]) {
            $this->uow->triggerEagerLoads();
        }

        $this->invokePostLoad($postLoad, $postLoadFlags);
        $this->uow->hydrationComplete();

        return $result;
    }

    /**
     * Starts a row by row hydration, see AbstractHydrator::toIterable().
     *
     * @param array<string, mixed> $hints
     */
    final public function beginIteration(array &$hints): void
    {
        [$slow, $postLoadFlags, $readOnly] = $this->prepare($hints);
        $this->iteration = ['slow' => $slow, 'postLoadFlags' => $postLoadFlags, 'readOnly' => $readOnly];
    }

    /**
     * Hydrates one row: like the Doctrine hydrators when iterating, nothing is shared between rows except the
     * unit of work.
     *
     * @param mixed[]              $row
     * @param array<string, mixed> $hints
     *
     * @return mixed[]
     */
    final public function hydrateRow(array $row, array &$hints): array
    {
        \assert(null !== $this->iteration);
        $postLoad = [];
        $result = $this->doHydrateRow($row, $hints, $this->iteration['slow'], $this->iteration['postLoadFlags'], $this->iteration['readOnly'], $postLoad);

        $this->invokePostLoad($postLoad, $this->iteration['postLoadFlags']);

        if (isset($hints[Query::HINT_INTERNAL_ITERATION]) && $hints[Query::HINT_INTERNAL_ITERATION]) {
            $this->uow->hydrationComplete();
        }

        return $result;
    }

    /**
     * Ends a row by row hydration, like ObjectHydrator::cleanup() and SimpleObjectHydrator::cleanup().
     *
     * @param array<string, mixed> $hints
     */
    final public function endIteration(array $hints): void
    {
        $this->iteration = null;

        if (HydrationPlan::SIMPLE === static::MODE || true === ($hints[UnitOfWork::HINT_DEFEREAGERLOAD] ?? null)) {
            $this->uow->triggerEagerLoads();
        }

        $this->uow->hydrationComplete();
    }

    public function onClear(): void
    {
        $this->cleared = true;
    }

    /**
     * Reference of a not fetch joined to-one association, like UnitOfWork::createEntity().
     */
    public function reference(int $classIndex, mixed $id): object
    {
        $class = $this->classes[$classIndex];
        $entity = $this->uow->tryGetByIdHash($id instanceof \BackedEnum ? $id->value : $id, $class->rootEntityName);

        if (false !== $entity) {
            return $entity;
        }

        $identifier = [$class->identifier[0] => $id];
        $proxy = $this->em->getProxyFactory()->getProxy($class->name, $identifier);
        $this->uow->registerManaged($proxy, $identifier, []);

        return $proxy;
    }

    /**
     * Same as {@see self::reference()} when the target has a composite identifier.
     *
     * @param array<string, mixed> $associatedId identifier fields in the order of the join columns
     */
    public function referenceComposite(int $classIndex, array $associatedId): object
    {
        $class = $this->classes[$classIndex];
        $entity = $this->uow->tryGetByIdHash(UnitOfWork::getIdHashByIdentifier($associatedId), $class->rootEntityName);

        if (false !== $entity) {
            return $entity;
        }

        $normalized = [];

        foreach ($class->getIdentifierFieldNames() as $name) {
            if (\array_key_exists($name, $associatedId)) {
                $normalized[$name] = $associatedId[$name];
            }
        }

        $proxy = $this->em->getProxyFactory()->getProxy($class->name, $normalized);
        $this->uow->registerManaged($proxy, $associatedId, []);

        return $proxy;
    }

    /**
     * Same conversion as AbstractHydrator::buildEnum().
     *
     * @param class-string<\BackedEnum> $enumType
     *
     * @return \BackedEnum|array<\BackedEnum>
     */
    public function enum(mixed $value, string $enumType): \BackedEnum|array
    {
        $isIntBacked = $this->intBackedEnums[$enumType] ??= 'int' === (string) (new \ReflectionEnum($enumType))->getBackingType();

        $from = static function (mixed $value) use ($enumType, $isIntBacked): \BackedEnum {
            /** @var int|string $value */
            return $enumType::from($isIntBacked ? (int) $value : $value);
        };

        return \is_array($value) ? array_map($from, $value) : $from($value);
    }

    /**
     * Enum conversion of the SimpleObjectHydrator, which reports invalid values with a MappingException.
     *
     * @param class-string<\BackedEnum> $enumType
     * @param class-string              $className
     *
     * @return \BackedEnum|array<\BackedEnum|array<\BackedEnum>>
     */
    public function simpleEnum(mixed $value, string $enumType, string $className, string $field): \BackedEnum|array
    {
        if (!\is_array($value)) {
            return $this->simpleEnumValue($value, $enumType, $className, $field);
        }

        $enums = [];

        foreach ($value as $i => $currentValue) {
            $enums[$i] = $this->simpleEnumValue($currentValue, $enumType, $className, $field);
        }

        return $enums;
    }

    abstract protected function initialize(): void;

    /**
     * @param array<string, mixed>         $hints
     * @param array<int, array<int, bool>> $slow
     * @param array<int, int<0, 7>>        $postLoadFlags
     * @param list<array{int, object}>     $postLoad
     *
     * @return mixed[]
     */
    abstract protected function doHydrate(Result $stmt, array &$hints, array $slow, array $postLoadFlags, bool $readOnly, array &$postLoad): array;

    /**
     * @param mixed[]                      $row
     * @param array<string, mixed>         $hints
     * @param array<int, array<int, bool>> $slow
     * @param array<int, int<0, 7>>        $postLoadFlags
     * @param list<array{int, object}>     $postLoad
     *
     * @return mixed[]
     */
    abstract protected function doHydrateRow(array $row, array &$hints, array $slow, array $postLoadFlags, bool $readOnly, array &$postLoad): array;

    /**
     * Concrete class of an alias of an inheritance hierarchy, with the checks of the Doctrine hydrators.
     */
    protected function discriminate(int $aliasIndex, mixed $value): int
    {
        $discriminator = static::DISCRIMINATORS[$aliasIndex];

        if (null === $value) {
            throw HydrationException::missingDiscriminatorColumn($discriminator['class'], $discriminator['name'], $discriminator['alias']);
        }

        if ('' === $value) {
            throw HydrationException::emptyDiscriminatorValue($discriminator['alias']);
        }

        if ($value instanceof \BackedEnum) {
            $value = $value->value;
        }

        // array keys: the (string) cast of the ObjectHydrator and the raw value of the SimpleObjectHydrator are equivalent
        $key = \is_scalar($value) ? (string) $value : '';

        if (!isset($discriminator['map'][$key])) {
            throw HydrationException::invalidDiscriminatorValue($key, array_keys($discriminator['map']));
        }

        return $discriminator['map'][$key];
    }

    /**
     * @param array<string, mixed> $data
     * @param array<string, mixed> $hints
     */
    protected function createEntity(string $alias, int $classIndex, array $data, array &$hints): object
    {
        if (HydrationPlan::OBJECT === static::MODE) {
            $hints['fetchAlias'] = $alias;
        }

        return $this->uow->createEntity($this->classes[$classIndex]->name, $data, $hints);
    }

    /**
     * Same as ObjectHydrator::initRelatedCollection(), returns false when the existing collection must be kept as is.
     *
     * @param array<string, mixed>                                         $hints
     * @param array<string, PersistentCollection<array-key, object>|false> $own
     * @param list<PersistentCollection<array-key, object>>                $snapshots
     *
     * @return PersistentCollection<array-key, object>|false
     */
    protected function initRelatedCollection(object $entity, int $classIndex, string $field, string $parentAlias, array $hints, array &$own, array &$snapshots): PersistentCollection|false
    {
        $oid = spl_object_id($entity);
        $key = $oid . $field;

        if (isset($own[$key])) {
            return $own[$key];
        }

        $class = $this->classes[$classIndex];
        $relation = $class->associationMappings[$field];
        $value = $class->propertyAccessors[$field]->getValue($entity);

        if (null === $value || \is_array($value)) {
            $value = new ArrayCollection((array) $value);
        }

        if (!$value instanceof PersistentCollection) {
            \assert($relation->isToMany() && $value instanceof ArrayCollection);
            /** @var PersistentCollection<array-key, object> $value */
            $value = new PersistentCollection($this->em, $this->em->getClassMetadata($relation->targetEntity), $value);
            $value->setOwner($entity, $relation);

            $class->propertyAccessors[$field]->setValue($entity, $value);
            $this->uow->setOriginalEntityProperty($oid, $field, $value);

            return $own[$key] = $snapshots[] = $value;
        }

        /** @var PersistentCollection<array-key, object> $value */
        if (isset($hints[Query::HINT_REFRESH]) || (self::isFetched($hints, $parentAlias, $field) && !$value->isInitialized())) {
            $value->setDirty(false);
            $value->setInitialized(true);
            $value->unwrap()->clear();

            return $own[$key] = $snapshots[] = $value;
        }

        return $own[$key] = false;
    }

    /**
     * A fetch joined collection without any element in the result set, for an entity that was already managed.
     *
     * @param array<string, mixed>                                         $hints
     * @param array<string, PersistentCollection<array-key, object>|false> $own
     * @param list<PersistentCollection<array-key, object>>                $snapshots
     * @param array<int, PersistentCollection<array-key, mixed>>           $uninitialized
     */
    protected function emptyCollection(object $entity, int $classIndex, string $field, string $parentAlias, array $hints, array &$own, array &$snapshots, array &$uninitialized): void
    {
        $value = $this->classes[$classIndex]->propertyAccessors[$field]->getValue($entity);

        if (!$value) {
            $this->initRelatedCollection($entity, $classIndex, $field, $parentAlias, $hints, $own, $snapshots);

            return;
        }

        if ($value instanceof PersistentCollection && !$value->isInitialized()) {
            /** @var PersistentCollection<array-key, mixed> $collection */
            $collection = $value;
            $uninitialized[spl_object_id($collection)] = $collection;
        }
    }

    /**
     * Sets a fetch joined to-one association on an entity created by this hydration.
     */
    protected function linkToOne(int $classIndex, string $field, object $parent, ?object $element): void
    {
        $class = $this->classes[$classIndex];
        $oid = spl_object_id($parent);

        if (null === $element) {
            $this->uow->setOriginalEntityProperty($oid, $field, null);
            $class->propertyAccessors[$field]->setValue($parent, null);

            return;
        }

        $class->propertyAccessors[$field]->setValue($parent, $element);
        $this->uow->setOriginalEntityProperty($oid, $field, $element);

        $relation = $class->associationMappings[$field];
        $targetClass = $this->em->getClassMetadata($relation->targetEntity);

        if ($relation->isOwningSide()) {
            if (null !== $relation->inversedBy) {
                $inverseAssoc = $targetClass->associationMappings[$relation->inversedBy];

                if ($inverseAssoc->isToOne()) {
                    $targetClass->propertyAccessors[$inverseAssoc->fieldName]->setValue($element, $parent);
                    $this->uow->setOriginalEntityProperty(spl_object_id($element), $inverseAssoc->fieldName, $parent);
                }
            }

            return;
        }

        $targetClass->propertyAccessors[$relation->mappedBy]->setValue($element, $parent);
        $this->uow->setOriginalEntityProperty(spl_object_id($element), $relation->mappedBy, $parent);
    }

    /**
     * Fetch joined to-one association of an entity that was already managed, like the ObjectHydrator.
     *
     * @param array<string, mixed>|null $childData null when the joined entity is absent from the row
     * @param array<string, mixed>      $hints
     */
    protected function toOneOfExisting(int $classIndex, string $field, object $parent, string $childAlias, int $childClassIndex, ?array $childData, array &$hints): ?object
    {
        $value = $this->classes[$classIndex]->propertyAccessors[$field]->getValue($parent);

        if (!$value || isset($hints[Query::HINT_REFRESH]) || $this->uow->isUninitializedObject($value)) {
            if (null === $childData) {
                $this->linkToOne($classIndex, $field, $parent, null);

                return null;
            }

            $element = $this->createEntity($childAlias, $childClassIndex, $childData, $hints);
            $this->linkToOne($classIndex, $field, $parent, $element);

            return $element;
        }

        \assert(\is_object($value));

        return $value;
    }

    /**
     * @param array<string, mixed> $hints
     *
     * @return array{array<int, array<int, bool>>, array<int, int<0, 7>>, bool}
     */
    private function prepare(array &$hints): array
    {
        if (HydrationPlan::OBJECT === static::MODE) {
            // same defaults as ObjectHydrator::prepare()
            if (!isset($hints[UnitOfWork::HINT_DEFEREAGERLOAD])) {
                $hints[UnitOfWork::HINT_DEFEREAGERLOAD] = true;
            }

            /** @var array<string, array<string, bool>> $fetched */
            $fetched = $hints['fetched'] ?? [];

            foreach (static::FETCHED as $alias => $fields) {
                foreach ($fields as $field => $_) {
                    $fetched[$alias][$field] = true;
                }
            }

            $hints['fetched'] = $fetched;
        }

        $slow = [];

        foreach (static::ALIASES as $index => [$alias, $classIndexes]) {
            foreach ($classIndexes as $classIndex) {
                $slow[$index][$classIndex] = isset(static::SLOW[$index][$classIndex]) || $this->hasEagerAssociation($alias, $this->classes[$classIndex], $hints);
            }
        }

        $postLoadFlags = [];

        foreach ($this->classes as $classIndex => $class) {
            $postLoadFlags[$classIndex] = $this->listenersInvoker->getSubscribedSystems($class, Events::postLoad);
        }

        return [$slow, $postLoadFlags, isset($hints[Query::HINT_READ_ONLY]) && true === $hints[Query::HINT_READ_ONLY]];
    }

    /**
     * @param list<array{int, object}> $postLoad
     * @param array<int, int<0, 7>>    $postLoadFlags
     */
    private function invokePostLoad(array $postLoad, array $postLoadFlags): void
    {
        foreach ($postLoad as [$classIndex, $entity]) {
            $this->listenersInvoker->invoke($this->classes[$classIndex], Events::postLoad, $entity, new PostLoadEventArgs($entity, $this->em), $postLoadFlags[$classIndex]);
        }
    }

    /**
     * @param ClassMetadata<object> $class
     * @param array<string, mixed>  $hints
     */
    private function hasEagerAssociation(string $alias, ClassMetadata $class, array $hints): bool
    {
        foreach ($class->associationMappings as $field => $assoc) {
            if (self::isFetched($hints, $alias, $field)) {
                continue;
            }

            /** @var array<string, array<string, int>> $fetchModes */
            $fetchModes = $hints['fetchMode'] ?? [];

            if (ClassMetadata::FETCH_EAGER === ($fetchModes[$class->name][$field] ?? $assoc->fetch)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param array<string, mixed> $hints
     */
    private static function isFetched(array $hints, string $alias, string $field): bool
    {
        /** @var array<string, array<string, bool>> $fetched */
        $fetched = $hints['fetched'] ?? [];

        return isset($fetched[$alias][$field]);
    }

    /**
     * @param class-string<\BackedEnum> $enumType
     * @param class-string              $className
     */
    private function simpleEnumValue(mixed $value, string $enumType, string $className, string $field): \BackedEnum
    {
        try {
            $enum = $this->enum($value, $enumType);
        } catch (\ValueError $e) {
            throw MappingException::invalidEnumValue($className, $field, \is_scalar($value) ? (string) $value : get_debug_type($value), $enumType, $e);
        }

        \assert($enum instanceof \BackedEnum);

        return $enum;
    }
}
