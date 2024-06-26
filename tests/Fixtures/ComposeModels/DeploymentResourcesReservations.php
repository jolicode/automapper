<?php

namespace AutoMapper\Tests\Fixtures\ComposeModels;

class DeploymentResourcesReservations
{
    public function __construct(public float|string|null $cpus = NULL, public string|null $memory = NULL)
    {
    }
}