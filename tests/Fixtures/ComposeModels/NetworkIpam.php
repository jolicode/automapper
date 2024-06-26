<?php

namespace AutoMapper\Tests\Fixtures\ComposeModels;

class NetworkIpam
{
    public function __construct(
        public string|null $driver = NULL,
        /** @var NetworkIpamConfigItem|null[]|null */
        public array|null $config = NULL,
        public string|null $options = NULL
    )
    {
    }
}