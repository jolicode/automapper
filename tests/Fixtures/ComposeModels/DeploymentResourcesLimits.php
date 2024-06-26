<?php

namespace AutoMapper\Tests\Fixtures\ComposeModels;

class DeploymentResourcesLimits
{
    public function __construct(public float|string|null $cpus = NULL, public string|null $memory = NULL, public int|null $pids = NULL)
    {
    }
}