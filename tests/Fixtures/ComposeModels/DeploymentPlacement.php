<?php

namespace AutoMapper\Tests\Fixtures\ComposeModels;

class DeploymentPlacement
{
    public function __construct(
        /** @var string|null[]|null */
        public array|null $constraints = NULL,
        /** @var DeploymentPlacementPreferencesItem|null[]|null */
        public array|null $preferences = NULL,
        public int|null $maxReplicasPerNode = NULL
    )
    {
    }
}