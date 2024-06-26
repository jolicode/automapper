<?php

namespace AutoMapper\Tests\Fixtures\ComposeModels;

class Config
{
    public function __construct(public string|null $name = NULL, public string|null $content = NULL, public string|null $environment = NULL, public string|null $file = NULL, public bool|ConfigExternal|null $external = NULL, public string|float|bool|null|array $labels = NULL, public string|null $templateDriver = NULL)
    {
    }
}