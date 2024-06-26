<?php

namespace AutoMapper\Tests\Fixtures\ComposeModels;

class Development
{
    public function __construct(
        /** @var DevelopmentWatchItem|null[]|null */
        public array|null $watch = NULL
    )
    {
    }
}