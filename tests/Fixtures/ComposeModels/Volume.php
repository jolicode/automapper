<?php

namespace AutoMapper\Tests\Fixtures\ComposeModels;

class Volume
{
    public function __construct(public string|null $name = NULL, public string|null $driver = NULL, public string|float|null $driverOpts = NULL, public bool|VolumeExternal|null $external = NULL, public string|float|bool|null|array $labels = NULL)
    {
    }
}