<?php

namespace AutoMapper\Tests\Fixtures\ComposeModels;

class ServicePortsItem
{
    public function __construct(public string|null $name = NULL, public string|null $mode = NULL, public string|null $hostIp = NULL, public int|null $target = NULL, public string|int|null $published = NULL, public string|null $protocol = NULL, public string|null $appProtocol = NULL)
    {
    }
}