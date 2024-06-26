<?php

namespace AutoMapper\Tests\Fixtures\ComposeModels;

class ServiceBuild
{
    public function __construct(
        public string|null $context = NULL,
        public string|null $dockerfile = NULL,
        public string|null $dockerfileInline = NULL,
        /** @var string|null[]|null */
        public array|null $entitlements = NULL,
        public string|float|bool|null|array $args = NULL,
        public string|float|bool|null|array $ssh = NULL,
        public string|float|bool|null|array $labels = NULL,
        /** @var string|null[]|null */
        public array|null $cacheFrom = NULL,
        /** @var string|null[]|null */
        public array|null $cacheTo = NULL,
        public bool|null $noCache = NULL,
        public string|float|bool|null|array $additionalContexts = NULL,
        public string|null $network = NULL,
        public bool|null $pull = NULL,
        public string|null $target = NULL,
        public int|string|null $shmSize = NULL,
        public string|float|bool|null|array $extraHosts = NULL,
        public string|null $isolation = NULL,
        public bool|null $privileged = NULL,
        /** @var string|null|ServiceConfigOrSecretItem[]|null */
        public array|null $secrets = NULL,
        /** @var string|null[]|null */
        public array|null $tags = NULL,
        public int|null|Ulimits $ulimits = NULL,
        /** @var string|null[]|null */
        public array|null $platforms = NULL
    )
    {
    }
}