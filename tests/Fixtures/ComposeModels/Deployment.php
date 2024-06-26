<?php

namespace AutoMapper\Tests\Fixtures\ComposeModels;

class Deployment
{
    public function __construct(public string|null $mode = NULL, public string|null $endpointMode = NULL, public int|null $replicas = NULL, public DeploymentRollbackConfig|null $rollbackConfig = NULL, public DeploymentUpdateConfig|null $updateConfig = NULL, public DeploymentResources|null $resources = NULL, public DeploymentRestartPolicy|null $restartPolicy = NULL, public DeploymentPlacement|null $placement = NULL)
    {
    }
}