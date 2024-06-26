<?php

namespace AutoMapper\Tests\Fixtures\ComposeModels;

class DeploymentResources
{
    public function __construct(public DeploymentResourcesLimits|null $limits = NULL, public DeploymentResourcesReservations|null $reservations = NULL)
    {
    }
}