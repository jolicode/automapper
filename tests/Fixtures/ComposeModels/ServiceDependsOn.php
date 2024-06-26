<?php

namespace AutoMapper\Tests\Fixtures\ComposeModels;

class ServiceDependsOn
{
    public function __construct(public bool|null $restart = NULL, public bool|null $required = true, public ServiceDependsOnConditionEnum|null $condition = NULL)
    {
    }
}