<?php

declare(strict_types=1);

namespace AutoMapper\Doctrine\Hydrator;

use AutoMapper\Doctrine\Hydrator\Plan\HydrationPlan;
use Doctrine\ORM\Internal\Hydration\AbstractHydrator;
use Doctrine\ORM\Internal\Hydration\SimpleObjectHydrator;

/**
 * Replaces the SimpleObjectHydrator (HYDRATE_SIMPLEOBJECT).
 */
final class AutoMapperSimpleObjectHydrator extends AutoMapperHydrator
{
    protected const string MODE = HydrationPlan::SIMPLE;

    protected function createFallback(): AbstractHydrator
    {
        return new SimpleObjectHydrator($this->em);
    }
}
