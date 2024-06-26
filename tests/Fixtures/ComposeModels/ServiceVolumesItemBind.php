<?php

namespace AutoMapper\Tests\Fixtures\ComposeModels;

class ServiceVolumesItemBind
{
    public function __construct(public string|null $propagation = NULL, public bool|null $createHostPath = NULL, public ServiceVolumesItemBindSelinuxEnum|null $selinux = NULL)
    {
    }
}