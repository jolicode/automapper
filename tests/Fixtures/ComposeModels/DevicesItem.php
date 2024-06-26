<?php

namespace AutoMapper\Tests\Fixtures\ComposeModels;

class DevicesItem
{
    public function __construct(
        /** @var string|null[]|null */
        public array|null $capabilities = NULL,
        public string|int|null $count = NULL,
        /** @var string|null[]|null */
        public array|null $deviceIds = NULL,
        public string|null $driver = NULL,
        public string|float|bool|null|array $options = NULL
    )
    {
    }
}