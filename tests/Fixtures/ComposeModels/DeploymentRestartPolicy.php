<?php

namespace AutoMapper\Tests\Fixtures\ComposeModels;

class DeploymentRestartPolicy
{
    public function __construct(public string|null $condition = NULL, public string|null $delay = NULL, public int|null $maxAttempts = NULL, public string|null $window = NULL)
    {
    }
}