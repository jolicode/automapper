<?php

namespace AutoMapper\Tests\Fixtures\ComposeModels;

class SecretExternal
{
    public function __construct(public string|null $name = NULL)
    {
    }
}