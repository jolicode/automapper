<?php

declare(strict_types=1);

namespace AutoMapper\Tests\AutoMapperTest\VariadicConstructor;

use AutoMapper\Tests\AutoMapperBuilder;

class Source
{
    public string $name = 'john';

    /** @var string[] */
    public array $tags = [];
}

class SourceWithoutTags
{
    public string $name = 'jane';
}

class Target
{
    /** @var string[] */
    public array $tags;

    public function __construct(
        public string $name,
        string ...$tags,
    ) {
        $this->tags = $tags;
    }
}

return (static function () {
    $autoMapper = AutoMapperBuilder::buildAutoMapper();

    $source = new Source();
    $source->tags = ['dev', 'php'];

    // variadic constructor parameter receives each value as its own argument
    yield 'with-values' => $autoMapper->map($source, Target::class);

    // absent variadic values: the parameter is optional, no exception
    yield 'without-values' => $autoMapper->map(new SourceWithoutTags(), Target::class);
})();
