<?php

namespace AutoMapper\Tests\Fixtures\ComposeModels;

class Secret
{
    public function __construct(public string|null $name = NULL, public string|null $environment = NULL, public string|null $file = NULL, public bool|SecretExternal|null $external = NULL, public string|float|bool|null|array $labels = NULL, public string|null $driver = NULL, public string|float|null $driverOpts = NULL, public string|null $templateDriver = NULL)
    {
    }
}