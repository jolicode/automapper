<?php

declare(strict_types=1);

namespace Automapper\Bench\Model;

/**
 * Plain public-property DTO, shaped so that every library under test
 * (json_encode/decode, AutoMapper, Symfony Serializer, Symfony JsonStreamer)
 * can (de)serialize it the same way with no per-library configuration.
 */
class Address
{
    public string $street = '';
    public string $city = '';
    public string $zipCode = '';
    public string $country = '';
}
