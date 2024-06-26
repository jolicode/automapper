<?php

namespace AutoMapper\Tests\Fixtures\ComposeModels;

class ServiceVolumesItemTmpfs
{
    public function __construct(public int|null|string $size = NULL, public float|null $mode = NULL)
    {
    }
}