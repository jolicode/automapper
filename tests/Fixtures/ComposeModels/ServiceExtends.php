<?php

namespace AutoMapper\Tests\Fixtures\ComposeModels;

class ServiceExtends
{
    public function __construct(public string|null $service = NULL, public string|null $file = NULL)
    {
    }
}