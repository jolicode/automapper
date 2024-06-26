<?php

namespace AutoMapper\Tests\Fixtures\ComposeModels;

class ServiceNetworks
{
    public function __construct(
        /** @var string|null[]|null */
        public array|null $aliases = NULL,
        public string|null $ipv4Address = NULL,
        public string|null $ipv6Address = NULL,
        /** @var string|null[]|null */
        public array|null $linkLocalIps = NULL,
        public string|null $macAddress = NULL,
        public string|float|null $driverOpts = NULL,
        public float|null $priority = NULL
    )
    {
    }
}