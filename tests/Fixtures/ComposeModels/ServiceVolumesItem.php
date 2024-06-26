<?php

namespace AutoMapper\Tests\Fixtures\ComposeModels;

class ServiceVolumesItem
{
    public function __construct(public string|null $type = NULL, public string|null $source = NULL, public string|null $target = NULL, public bool|null $readOnly = NULL, public string|null $consistency = NULL, public ServiceVolumesItemBind|null $bind = NULL, public ServiceVolumesItemVolume|null $volume = NULL, public ServiceVolumesItemTmpfs|null $tmpfs = NULL)
    {
    }
}