<?php

declare(strict_types=1);

namespace AutoMapper\Tests\Doctrine\Hydrator;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\PersistentCollection;
use Doctrine\ORM\UnitOfWork;

/**
 * Dumps a hydration result and the whole unit of work state as plain arrays, objects being replaced by their
 * discovery order, so two entity managers can be compared.
 */
final class UnitOfWorkDumper
{
    /** @var \SplObjectStorage<object, int> */
    private \SplObjectStorage $references;

    /** @var list<object> */
    private array $queue = [];

    /**
     * @return array<string, mixed>
     */
    public function dump(EntityManagerInterface $em, mixed $result): array
    {
        $this->references = new \SplObjectStorage();
        $this->queue = [];
        $uow = $em->getUnitOfWork();

        $dump = ['result' => $this->value($result), 'identityMap' => []];
        $identityMap = $uow->getIdentityMap();
        ksort($identityMap);

        foreach ($identityMap as $className => $entities) {
            ksort($entities, \SORT_STRING);

            foreach ($entities as $hash => $entity) {
                $dump['identityMap'][$className][(string) $hash] = $this->value($entity);
            }
        }

        $objects = [];

        while ($object = array_shift($this->queue)) {
            $objects[$this->references[$object]] = $this->describe($object, $uow);
        }

        ksort($objects);
        $dump['objects'] = $objects;

        $uow->computeChangeSets();
        $changeSets = [];

        foreach ($identityMap as $entities) {
            foreach ($entities as $entity) {
                if ($changeSet = $uow->getEntityChangeSet($entity)) {
                    ksort($changeSet);
                    $changeSets[$this->value($entity)] = $this->value($changeSet);
                }
            }
        }

        $dump['changeSets'] = $changeSets;
        $dump['scheduledCollectionUpdates'] = \count($uow->getScheduledCollectionUpdates());

        return $dump;
    }

    private function value(mixed $value): mixed
    {
        return match (true) {
            $value instanceof \DateTimeInterface => ['@date' => $value::class, 'value' => $value->format('Y-m-d H:i:s.u e')],
            $value instanceof \UnitEnum => ['@enum' => $value::class, 'value' => $value->name],
            \is_object($value) => '#' . $this->reference($value),
            \is_array($value) => array_map($this->value(...), $value),
            default => $value,
        };
    }

    private function reference(object $object): int
    {
        if (!isset($this->references[$object])) {
            $this->references[$object] = \count($this->references);
            $this->queue[] = $object;
        }

        return $this->references[$object];
    }

    /**
     * @return array<string, mixed>
     */
    private function describe(object $object, UnitOfWork $uow): array
    {
        if ($object instanceof PersistentCollection) {
            return [
                '@collection' => $object->getMapping()->fieldName,
                'owner' => $this->value($object->getOwner()),
                'initialized' => $object->isInitialized(),
                'dirty' => $object->isDirty(),
                'elements' => $this->value($object->unwrap()->toArray()),
                'snapshot' => $this->value($object->getSnapshot()),
                'pointer' => $object->unwrap()->key(),
            ];
        }

        if ($object instanceof ArrayCollection) {
            return ['@array_collection' => $this->value($object->toArray())];
        }

        $reflection = new \ReflectionClass($object);
        $description = ['@class' => $object::class];

        if ($reflection->isUninitializedLazyObject($object)) {
            $description['lazy'] = true;
        } else {
            for ($class = $reflection; false !== $class; $class = $class->getParentClass()) {
                foreach ($class->getProperties() as $property) {
                    if ($property->isStatic() || $property->getDeclaringClass()->name !== $class->name) {
                        continue;
                    }

                    $description[$class->getShortName() . '::' . $property->getName()] = $property->isInitialized($object)
                        ? $this->value($property->getRawValue($object))
                        : '@uninitialized';
                }
            }
        }

        if ($uow->isInIdentityMap($object)) {
            $original = $uow->getOriginalEntityData($object);
            ksort($original);

            $description['state'] = $uow->getEntityState($object);
            $description['identifier'] = $this->value($uow->getEntityIdentifier($object));
            $description['original'] = $this->value($original);
            $description['readOnly'] = $uow->isReadOnly($object);
        }

        return $description;
    }
}
