<?php

declare(strict_types=1);

namespace AutoMapper\Tests\Bundle\Resources\App\Service;

use AutoMapper\Metadata\MapperMetadata;
use AutoMapper\Metadata\SourcePropertyMetadata;
use AutoMapper\Metadata\TargetPropertyMetadata;
use AutoMapper\Metadata\TypesMatching;
use AutoMapper\Tests\Bundle\Fixtures\User;
use AutoMapper\Tests\Bundle\Fixtures\UserDTO;
use AutoMapper\Transformer\PropertyTransformer\PropertyTransformerInterface;
use AutoMapper\Transformer\PropertyTransformer\PropertyTransformerSupportInterface;

class YearOfBirthTransformer implements PropertyTransformerInterface, PropertyTransformerSupportInterface
{
    public function transform(mixed $value, object|array $user, array $context): mixed
    {
        \assert($user instanceof User);

        return ((int) date('Y')) - ((int) $user->age);
    }

    public function supports(TypesMatching $types, SourcePropertyMetadata $source, TargetPropertyMetadata $target, MapperMetadata $mapperMetadata): bool
    {
        return User::class === $mapperMetadata->source && UserDTO::class === $mapperMetadata->target && 'yearOfBirth' === $source->name;
    }
}
