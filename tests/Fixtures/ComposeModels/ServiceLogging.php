<?php

namespace AutoMapper\Tests\Fixtures\ComposeModels;

class ServiceLogging
{
    public function __construct(public string|null $driver = NULL, public string|float|null $options = NULL)
    {
    }
}