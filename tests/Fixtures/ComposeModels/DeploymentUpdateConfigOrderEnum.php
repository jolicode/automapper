<?php

namespace AutoMapper\Tests\Fixtures\ComposeModels;

enum DeploymentUpdateConfigOrderEnum : string
{
    case START_FIRST = 'start-first';
    case STOP_FIRST = 'stop-first';
}