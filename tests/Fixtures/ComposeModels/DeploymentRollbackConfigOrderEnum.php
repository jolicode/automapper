<?php

namespace AutoMapper\Tests\Fixtures\ComposeModels;

enum DeploymentRollbackConfigOrderEnum : string
{
    case START_FIRST = 'start-first';
    case STOP_FIRST = 'stop-first';
}