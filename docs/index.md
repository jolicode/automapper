# 🚀 Very FAST 🚀 PHP AutoMapper with on the fly code generation

## Presentation

Welcome to the AutoMapper documentation, this library solves a simple problem: removing all the code you need to
map one object to another. A boring code to write and often replaced by less-performant alternatives.

AutoMapper uses a convention-based matching algorithm to match up source to destination values. AutoMapper is geared
towards model projection scenarios to flatten complex object models to DTOs and other simple objects, whose design is
better suited for serialization, communication, messaging, or simply an anti-corruption layer between the domain and
application layer.

## Usage 🕹️

Here is the quickest way to use AutoMapper:

```php
use AutoMapper\AutoMapper;

$automapper = AutoMapper::create();

$source = new Source();
$target = $automapper->map($source, Target::class);
```

That's it! `AutoMapper` will find the best way to map from `$source` object to a new `Target` object. It will also
generate a PHP class that will do the mapping for you and serve as a cache for future mapping.

Of course there are many ways to customize the mapping, this documentation will explain all of them.

## Installation 📦

```shell
composer require jolicode/automapper
```

## Serializer

There is more than object to object mapping with AutoMapper. You can also map to or from generic data structure like
`array` or `stdClass`. When you map to an `array`, AutoMapper will try its best to only use scalar values, so you can
serialize the result to JSON or XML.


## Why should I use it ? 🙋

The real question may be “why use object-object mapping?” Mapping can occur in many places in an application, but
mostly in the boundaries between layers, such as between the UI/Domain layers, or Service/Domain layers. Concerns of
one layer often conflict with concerns in another, so object-object mapping leads to segregated models, where concerns
for each layer can affect only types in that layer.

## Further reading 📚

- [Getting Started](getting-started/index.md)
- [How to customize the mapping?](mapping/index.md)
- [Using the Symfony Bundle](bundle/index.md)
