<?php

namespace AutoMapper\Tests\Fixtures\ComposeModels;

enum DevelopmentWatchItemActionEnum : string
{
    case REBUILD = 'rebuild';
    case SYNC = 'sync';
    case SYNC_RESTART = 'sync+restart';
}