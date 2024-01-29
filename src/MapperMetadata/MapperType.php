<?php

declare(strict_types=1);

namespace AutoMapper\MapperMetadata;

enum MapperType
{
    case FROM_SOURCE;
    case FROM_TARGET;
    case SOURCE_TARGET;
}
