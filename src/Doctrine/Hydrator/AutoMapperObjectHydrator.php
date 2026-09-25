<?php

declare(strict_types=1);

namespace AutoMapper\Doctrine\Hydrator;

use AutoMapper\Doctrine\Hydrator\Plan\HydrationPlan;
use Doctrine\ORM\Internal\Hydration\AbstractHydrator;
use Doctrine\ORM\Internal\Hydration\ObjectHydrator;

/**
 * Replaces the ObjectHydrator (HYDRATE_OBJECT).
 */
final class AutoMapperObjectHydrator extends AutoMapperHydrator
{
    protected const string MODE = HydrationPlan::OBJECT;

    protected function createFallback(): AbstractHydrator
    {
        return new ObjectHydrator($this->em);
    }
}
