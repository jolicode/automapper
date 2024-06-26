<?php

namespace AutoMapper\Tests\Fixtures\ComposeModels;

class BlkioWeight
{
    public function __construct(public string|null $path = NULL, public int|null $weight = NULL)
    {
    }
}