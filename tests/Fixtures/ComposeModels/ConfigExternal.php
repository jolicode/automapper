<?php

namespace AutoMapper\Tests\Fixtures\ComposeModels;

class ConfigExternal
{
    public function __construct(
        /** @deprecated */
        public string|null $name = NULL
    )
    {
    }
}