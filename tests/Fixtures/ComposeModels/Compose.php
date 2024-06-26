<?php

namespace AutoMapper\Tests\Fixtures\ComposeModels;

class Compose
{
    public function __construct(
        public string|null $version = NULL,
        public string|null $name = NULL,
        /** @var string|null|_Include[]|null */
        public array|null $include = NULL,
        public Service|null $services = NULL,
        public Network|null $networks = NULL,
        public Volume|null $volumes = NULL,
        public Secret|null $secrets = NULL,
        public Config|null $configs = NULL
    )
    {
    }
}