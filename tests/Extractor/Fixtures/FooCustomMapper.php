<?php

declare(strict_types=1);

namespace AutoMapper\Tests\Extractor\Fixtures;

class FooCustomMapper
{
    public function transform(mixed $object): mixed
    {
        if ($object instanceof Foo) {
            $object->bar = 'Hello World!';
        }

        return $object;
    }

    public function switch(mixed $object, array $context): mixed
    {
        if ($object instanceof Foo) {
            $object->bar = 'Hello World!';
        }

        return $object;
    }
}
