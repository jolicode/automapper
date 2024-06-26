<?php

namespace AutoMapper\Tests\Fixtures\ComposeModels;

class Network
{
    public function __construct(public string|null $name = NULL, public string|null $driver = NULL, public string|float|null $driverOpts = NULL, public NetworkIpam|null $ipam = NULL, public bool|NetworkExternal|null $external = NULL, public bool|null $internal = NULL, public bool|null $enableIpv6 = NULL, public bool|null $attachable = NULL, public string|float|bool|null|array $labels = NULL)
    {
    }
}