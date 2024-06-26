<?php

namespace AutoMapper\Tests\Fixtures\ComposeModels;

class ServiceConfigOrSecretItem
{
    public function __construct(public string|null $source = NULL, public string|null $target = NULL, public string|null $uid = NULL, public string|null $gid = NULL, public float|null $mode = NULL)
    {
    }
}