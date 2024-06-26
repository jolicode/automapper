<?php

namespace AutoMapper\Tests\Fixtures\ComposeModels;

class ServiceVolumesItemVolume
{
    public function __construct(public bool|null $nocopy = NULL, public string|null $subpath = NULL)
    {
    }
}