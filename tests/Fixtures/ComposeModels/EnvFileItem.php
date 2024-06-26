<?php

namespace AutoMapper\Tests\Fixtures\ComposeModels;

class EnvFileItem
{
    public function __construct(public string|null $path = NULL, public bool|null $required = true)
    {
    }
}