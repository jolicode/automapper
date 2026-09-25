<?php

declare(strict_types=1);

namespace AutoMapper\Doctrine\Hydrator;

use Doctrine\ORM\Decorator\EntityManagerDecorator;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Internal\Hydration\AbstractHydrator;
use Doctrine\ORM\NativeQuery;
use Doctrine\ORM\Query;
use Doctrine\ORM\Query\ResultSetMapping;
use Doctrine\ORM\QueryBuilder;

/**
 * Entity manager using the generated hydrators for Query::HYDRATE_OBJECT and Query::HYDRATE_SIMPLEOBJECT.
 *
 * Queries, query builders and repositories are created with this entity manager so their hydrations go through it.
 * The entity persisters (find(), findBy(), lazy collections, ...) keep the Doctrine hydrators: they belong to the
 * unit of work of the wrapped entity manager.
 *
 * Replacing the hydration mode itself, rather than registering a custom one, matters: the SQL walker only selects
 * the foreign key columns needed for lazy references when the query uses HYDRATE_OBJECT.
 */
class AutoMapperEntityManager extends EntityManagerDecorator
{
    private readonly HydratorFactory $hydratorFactory;

    public function __construct(EntityManagerInterface $wrapped, ?HydratorFactory $hydratorFactory = null)
    {
        parent::__construct($wrapped);

        $this->hydratorFactory = $hydratorFactory ?? new HydratorFactory();
    }

    public function createQuery(string $dql = ''): Query
    {
        $query = new Query($this);

        if ('' !== $dql) {
            $query->setDQL($dql);
        }

        return $query;
    }

    public function createNativeQuery(string $sql, ResultSetMapping $rsm): NativeQuery
    {
        $query = new NativeQuery($this);
        $query->setSQL($sql);
        $query->setResultSetMapping($rsm);

        return $query;
    }

    public function createQueryBuilder(): QueryBuilder
    {
        return new QueryBuilder($this);
    }

    /**
     * @template T of object
     *
     * @param class-string<T> $className
     *
     * @return EntityRepository<T>
     */
    public function getRepository(string $className): EntityRepository
    {
        return $this->getConfiguration()->getRepositoryFactory()->getRepository($this, $className);
    }

    public function newHydrator(string|int $hydrationMode): AbstractHydrator
    {
        return match ($hydrationMode) {
            Query::HYDRATE_OBJECT => new AutoMapperObjectHydrator($this, $this->hydratorFactory),
            Query::HYDRATE_SIMPLEOBJECT => new AutoMapperSimpleObjectHydrator($this, $this->hydratorFactory),
            default => parent::newHydrator($hydrationMode),
        };
    }

    public function getHydratorFactory(): HydratorFactory
    {
        return $this->hydratorFactory;
    }
}
