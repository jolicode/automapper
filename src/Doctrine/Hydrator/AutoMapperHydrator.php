<?php

declare(strict_types=1);

namespace AutoMapper\Doctrine\Hydrator;

use AutoMapper\Doctrine\Hydrator\Plan\HydrationPlan;
use Doctrine\DBAL\Result;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Internal\Hydration\AbstractHydrator;
use Doctrine\ORM\Query\ResultSetMapping;

/**
 * Hydrates with the hydrator generated for the result set mapping, or delegates to the Doctrine hydrator when the
 * result set mapping or the hints are not supported.
 */
abstract class AutoMapperHydrator extends AbstractHydrator
{
    protected const string MODE = HydrationPlan::OBJECT;

    private static ?HydratorFactory $defaultFactory = null;

    private readonly HydratorFactory $factory;

    /** Generated hydrator of the current row by row hydration */
    private ?GeneratedHydrator $generated = null;

    /** Doctrine hydrator of the current row by row hydration */
    private ?AbstractHydrator $fallback = null;

    /**
     * The factory is optional because Doctrine instantiates custom hydration modes with the entity manager only,
     * see {@see self::setDefaultFactory()}.
     */
    public function __construct(EntityManagerInterface $em, ?HydratorFactory $factory = null)
    {
        parent::__construct($em);

        $this->factory = $factory ?? self::$defaultFactory ??= new HydratorFactory();
    }

    public static function setDefaultFactory(?HydratorFactory $factory): void
    {
        self::$defaultFactory = $factory;
    }

    public function hydrateAll(Result $stmt, ResultSetMapping $resultSetMapping, array $hints = []): mixed
    {
        $hydrator = $this->factory->getHydrator($this->em, $resultSetMapping, $hints, static::MODE);

        if (null === $hydrator) {
            return $this->createFallback()->hydrateAll($stmt, $resultSetMapping, $hints);
        }

        return $hydrator->hydrate($stmt, $hints);
    }

    public function onClear(mixed $eventArgs): void
    {
        $this->fallback?->onClear($eventArgs);
    }

    /**
     * The Doctrine hydrator used when the generated one cannot be.
     */
    abstract protected function createFallback(): AbstractHydrator;

    /**
     * Only reached by toIterable(): hydrateAll() is fully overridden.
     */
    protected function prepare(): void
    {
        $this->generated = $this->factory->getHydrator($this->em, $this->resultSetMapping(), $this->hints, static::MODE);

        if (null !== $this->generated) {
            $this->generated->beginIteration($this->hints);

            return;
        }

        // toIterable() is final: the Doctrine hydrator is driven with the state of this one
        $fallback = $this->createFallback();
        $fallback->stmt = $this->stmt;
        $fallback->rsm = $this->rsm;
        $fallback->hints = $this->hints;
        $fallback->prepare();
        $this->fallback = $fallback;
    }

    protected function hydrateRowData(array $row, array &$result): void
    {
        if (null !== $this->generated) {
            $result = $this->generated->hydrateRow($row, $this->hints);

            return;
        }

        \assert(null !== $this->fallback);
        $this->fallback->hydrateRowData($row, $result);
    }

    protected function cleanupAfterRowIteration(): void
    {
        $this->fallback?->cleanupAfterRowIteration();
    }

    protected function cleanup(): void
    {
        $generated = $this->generated;
        $fallback = $this->fallback;
        $this->generated = $this->fallback = null;

        $fallback?->cleanup();

        parent::cleanup();

        $generated?->endIteration($this->hints);
    }

    protected function hydrateAllData(): mixed
    {
        throw new \LogicException('hydrateAll() does not use hydrateAllData().');
    }
}
