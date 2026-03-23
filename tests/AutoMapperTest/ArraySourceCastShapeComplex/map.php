<?php

declare(strict_types=1);

namespace AutoMapper\Tests\AutoMapperTest\ArraySourceCastShapeComplex;

use AutoMapper\Tests\AutoMapperBuilder;

class Tag
{
    public string $label;
}

class Profile
{
    public string $name;
    public int $age;
}

class DtoObjectsAndScalars
{
    /** @var array{profile: Profile, tags: array<Tag>, score: int, active: bool} */
    public array $data;
}

class DtoNullableAndOptional
{
    /** @var array{name: string, bio?: ?string, age: int} */
    public array $data;
}

class DtoNestedShapeWithCollection
{
    /** @var array{meta: array{title: string, count: int}, items: array<Tag>} */
    public array $data;
}

class DtoDateTimeInShape
{
    /** @var array{label: string, created: \DateTimeInterface} */
    public array $data;
}

class DtoMixedDepth
{
    /** @var array{users: array<array{name: string, score: float}>} */
    public array $data;
}

return (function () {
    $autoMapper = AutoMapperBuilder::buildAutoMapper();

    // 1. Object field + collection of objects + scalar casts
    yield $autoMapper->map([
        'data' => [
            'profile' => ['name' => 'Alice', 'age' => 28],
            'tags' => [['label' => 'php'], ['label' => 'symfony']],
            'score' => '100',
            'active' => '1',
        ],
    ], DtoObjectsAndScalars::class);

    // 2. Nullable + optional field: present with value
    yield $autoMapper->map([
        'data' => ['name' => 'John', 'bio' => 'Hello world', 'age' => '30'],
    ], DtoNullableAndOptional::class);

    // 3. Nullable + optional field: present but null
    yield $autoMapper->map([
        'data' => ['name' => 'Jane', 'bio' => null, 'age' => '25'],
    ], DtoNullableAndOptional::class);

    // 4. Nullable + optional field: absent
    yield $autoMapper->map([
        'data' => ['name' => 'Bob', 'age' => '40'],
    ], DtoNullableAndOptional::class);

    // 5. Nested shape containing a collection of objects
    yield $autoMapper->map([
        'data' => [
            'meta' => ['title' => 'Test', 'count' => '7'],
            'items' => [['label' => 'a'], ['label' => 'b']],
        ],
    ], DtoNestedShapeWithCollection::class);

    // 6. DateTime inside shape
    yield $autoMapper->map([
        'data' => ['label' => 'event', 'created' => '2025-01-15T10:30:00+00:00'],
    ], DtoDateTimeInShape::class);

    // 7. Collection of shapes (array<array{name: string, score: float}>)
    yield $autoMapper->map([
        'data' => [
            'users' => [
                ['name' => 'Alice', 'score' => '9.5'],
                ['name' => 'Bob', 'score' => '8.0'],
            ],
        ],
    ], DtoMixedDepth::class);
})();
