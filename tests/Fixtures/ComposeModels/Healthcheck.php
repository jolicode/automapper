<?php

namespace AutoMapper\Tests\Fixtures\ComposeModels;

class Healthcheck
{
    public function __construct(public bool|null $disable = NULL, public string|null $interval = NULL, public float|null $retries = NULL, public string|null|array $test = NULL, public string|null $timeout = NULL, public string|null $startPeriod = NULL, public string|null $startInterval = NULL)
    {
    }
}