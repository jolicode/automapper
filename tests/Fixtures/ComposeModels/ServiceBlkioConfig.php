<?php

namespace AutoMapper\Tests\Fixtures\ComposeModels;

class ServiceBlkioConfig
{
    public function __construct(
        /** @var BlkioLimit|null[]|null */
        public array|null $deviceReadBps = NULL,
        /** @var BlkioLimit|null[]|null */
        public array|null $deviceReadIops = NULL,
        /** @var BlkioLimit|null[]|null */
        public array|null $deviceWriteBps = NULL,
        /** @var BlkioLimit|null[]|null */
        public array|null $deviceWriteIops = NULL,
        public int|null $weight = NULL,
        /** @var BlkioWeight|null[]|null */
        public array|null $weightDevice = NULL
    )
    {
    }
}