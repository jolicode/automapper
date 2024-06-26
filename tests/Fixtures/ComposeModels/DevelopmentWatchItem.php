<?php

namespace AutoMapper\Tests\Fixtures\ComposeModels;

class DevelopmentWatchItem
{
    public function __construct(
        /** @var string|null[]|null */
        public array|null $ignore = NULL,
        public string|null $path = NULL,
        public DevelopmentWatchItemActionEnum|null $action = NULL,
        public string|null $target = NULL
    )
    {
    }
}