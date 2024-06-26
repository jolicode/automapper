<?php

namespace AutoMapper\Tests\Fixtures\ComposeModels;

class ServiceCredentialSpec
{
    public function __construct(public string|null $config = NULL, public string|null $file = NULL, public string|null $registry = NULL)
    {
    }
}