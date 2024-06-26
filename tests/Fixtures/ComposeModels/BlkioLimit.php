<?php

namespace AutoMapper\Tests\Fixtures\ComposeModels;

class BlkioLimit
{
    public function __construct(public string|null $path = NULL, public int|string|null $rate = NULL)
    {
    }
}