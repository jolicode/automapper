<?php

namespace AutoMapper\Tests\Fixtures\ComposeModels;

class DeploymentUpdateConfig
{
    public function __construct(public int|null $parallelism = NULL, public string|null $delay = NULL, public string|null $failureAction = NULL, public string|null $monitor = NULL, public float|null $maxFailureRatio = NULL, public DeploymentUpdateConfigOrderEnum|null $order = NULL)
    {
    }
}