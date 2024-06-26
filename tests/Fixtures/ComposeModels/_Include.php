<?php

namespace AutoMapper\Tests\Fixtures\ComposeModels;

class _Include
{
    public function __construct(public string|null $path = NULL, public string|null $envFile = NULL, public string|null $projectDirectory = NULL)
    {
    }
}