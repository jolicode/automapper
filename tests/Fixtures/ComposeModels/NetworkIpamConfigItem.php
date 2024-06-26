<?php

namespace AutoMapper\Tests\Fixtures\ComposeModels;

class NetworkIpamConfigItem
{
    public function __construct(public string|null $subnet = NULL, public string|null $ipRange = NULL, public string|null $gateway = NULL, public string|null $auxAddresses = NULL)
    {
    }
}