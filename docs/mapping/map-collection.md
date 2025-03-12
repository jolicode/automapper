# Mapping Collections

When working with AutoMapper, you often need to map not just a single object, but a collection of objects. The `mapCollection()` method is designed specifically for this purpose.

## Basic Usage

The `mapCollection()` method allows you to transform a collection of objects from one type to another:

```php
use AutoMapper\AutoMapper;

$automapper = AutoMapper::create();

$sources = [
    new Source('value1'),
    new Source('value2'),
    new Source('value3'),
];

$targets = $automapper->mapCollection($sources, Target::class);
```

This will return an array where each element from the source collection is mapped to a new instance of the target class.

## Collection Types Support

AutoMapper's `mapCollection()` supports any `iterable`

The method will return an array of object that was provided as a target class:

```php
$targetsArray = $automapper->mapCollection($sourcesArray, Target::class);
```

## Using Context

Just like the `map()` method, `mapCollection()` also accepts a context parameter for customizing the mapping process:

```php
$context = ['groups' => ['read']];
$targets = $automapper->mapCollection($sources, Target::class, $context);
```

This context will be applied to each individual object mapping operation.