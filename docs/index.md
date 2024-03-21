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
$destination = $automapper->map($source, Destination::class);
```

That's it! `AutoMapper` will find the best way to map from `$source` object to the `Destination` object. It will also
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

- [Getting Started](getting-started.md)
- [Configuration](configuration.md)
- [How to customize the mapping?](mapping/index.md)
- [Using the Symfony Bundle](bundle/index.md)
- [Frequently asked questions](faq.md)

[//]: # ()
[//]: # (### How to use it ? 🕹️)

[//]: # ()
[//]: # (First, you need both a source and destination type to work with. The destination type’s design can be influenced by the )

[//]: # (layer in which it lives, but the AutoMapper works best as long as the names of the members match up to the source )

[//]: # (type’s members. If you have a source member called "firstName", this will automatically be mapped to a destination )

[//]: # (member with the name "firstName".)

[//]: # ()
[//]: # (```php)

[//]: # (class InputUser)

[//]: # ({)

[//]: # (  public function __construct&#40;)

[//]: # (    public readonly string $firstName,)

[//]: # (    public readonly string $lastName,)

[//]: # (    public readonly int $age,)

[//]: # (  &#41; {)

[//]: # (  })

[//]: # (})

[//]: # ()
[//]: # (class DatabaseUser)

[//]: # ({)

[//]: # (  public function __construct&#40;)

[//]: # (    #[ORM\Column])

[//]: # (    public string $firstName,)

[//]: # (    #[ORM\Column])

[//]: # (    public string $lastName,)

[//]: # (    #[ORM\Column])

[//]: # (    public int $age,)

[//]: # (  &#41; {)

[//]: # (  })

[//]: # (})

[//]: # ()
[//]: # ($automapper = \AutoMapper\AutoMapper::create&#40;&#41;;)

[//]: # (dump&#40;$automapper->map&#40;new InputUser&#40;'John', 'Doe', 28&#41;, DatabaseUser::class&#41;&#41;;)

[//]: # ()
[//]: # (// ^ DatabaseUser^ {#1383)

[//]: # (//   +firstName: "John")

[//]: # (//   +lastName: "Doe")

[//]: # (//   +age: 28)

[//]: # (// })

[//]: # (```)

[//]: # ()
[//]: # (### How to customize the mapping? 🚀)

[//]: # ()
[//]: # (The mapping process could be extended in multiple ways.)

[//]: # ()
[//]: # (#### Using the `#[MapTo]` attribute)

[//]: # ()
[//]: # (You can use the `#[MapTo]` attribute to specify the target name of a property. This is useful when the property name is )

[//]: # (different in the source and target classes.)

[//]: # ()
[//]: # (```php)

[//]: # (class InputUser)

[//]: # ({)

[//]: # (  public function __construct&#40;)

[//]: # (    public readonly string $firstName,)

[//]: # (    public readonly string $lastName,)

[//]: # (    #[MapTo&#40;name: 'userAge'&#41;])

[//]: # (    public readonly int $age,)

[//]: # (  &#41; {)

[//]: # (  })

[//]: # (})

[//]: # (```)

[//]: # ()
[//]: # (This will map the `age` property to the `userAge` property in the all the target classes. If you want to map the `age`)

[//]: # (to a specific target class, you can use the `#[MapTo]` target argument to specify the target class.)

[//]: # ()
[//]: # (```php)

[//]: # (class InputUser)

[//]: # ({)

[//]: # (  public function __construct&#40;)

[//]: # (    public readonly string $firstName,)

[//]: # (    public readonly string $lastName,)

[//]: # (    #[MapTo&#40;name: 'userAge', target: DatabaseUser::class&#41;])

[//]: # (    public readonly int $age,)

[//]: # (  &#41; {)

[//]: # (  })

[//]: # (})

[//]: # (```)

[//]: # ()
[//]: # (#### Ignoring a field)

[//]: # ()
[//]: # (You can ignore a field by using the `#[MapTo]` attribute.)

[//]: # ()
[//]: # (```php)

[//]: # (class InputUser)

[//]: # ({)

[//]: # (  public function __construct&#40;)

[//]: # (    public readonly string $firstName,)

[//]: # (    public readonly string $lastName,)

[//]: # (    #[MapTo&#40;ignore: true, target: DatabaseUser::class&#41;])

[//]: # (    public readonly int $age,)

[//]: # (  &#41; {)

[//]: # (  })

[//]: # (})

[//]: # (```)

[//]: # ()
[//]: # (Ignoring a field can be useful when mapping a field to an array, as by default the `#[MapTo]` attribute with a specific)

[//]: # (name will add the field to the mapping but does not replace it, by example:)

[//]: # ()
[//]: # (```php)

[//]: # (class InputUser)

[//]: # ({)

[//]: # (  public function __construct&#40;)

[//]: # (    public readonly string $firstName,)

[//]: # (    public readonly string $lastName,)

[//]: # (    #[MapTo&#40;name: 'userAge', target: 'array'&#41;])

[//]: # (    public readonly int $age,)

[//]: # (  &#41; {)

[//]: # (  })

[//]: # (})

[//]: # (```)

[//]: # ()
[//]: # (When mapping a `InputUser` to an array, it will have both `age` and `userAge` fields. You can ignore the `age` field,)

[//]: # (by using the `#[MapTo]` attribute:)

[//]: # ()
[//]: # (```php)

[//]: # (class InputUser)

[//]: # ({)

[//]: # (  public function __construct&#40;)

[//]: # (    public readonly string $firstName,)

[//]: # (    public readonly string $lastName,)

[//]: # (    #[MapTo&#40;name: 'userAge', target: 'array'&#41;])

[//]: # (    #[MapTo&#40;ignore: true, target: 'array'&#41;])

[//]: # (    public readonly int $age,)

[//]: # (  &#41; {)

[//]: # (  })

[//]: # (})

[//]: # (```)

[//]: # ()
[//]: # (#### Using the `#[MapFrom]` attribute)

[//]: # ()
[//]: # (You can use the `#[MapFrom]` attribute to specify the source name of a property. It works the same way as the `#[MapTo]`)

[//]: # (attribute but for the source class. This is useful when the source is an array or a `stdClass` object.)

[//]: # ()
[//]: # (```php)

[//]: # (class InputUser)

[//]: # ({)

[//]: # (  public function __construct&#40;)

[//]: # (    public readonly string $firstName,)

[//]: # (    public readonly string $lastName,)

[//]: # (    #[MapFrom&#40;name: 'userAge', source: 'array'&#41;])

[//]: # (    public readonly int $age,)

[//]: # (  &#41; {)

[//]: # (  })

[//]: # (})

[//]: # (```)

[//]: # ()
[//]: # (#### Using a custom transformer)

[//]: # ()
[//]: # (You can use a custom transformer to transform the value of a property. This is useful when you need to transform the)

[//]: # (value of a property before mapping it to the target class.)

[//]: # ()
[//]: # (```php)

[//]: # (class InputUser)

[//]: # ({)

[//]: # (  public function __construct&#40;)

[//]: # (    public readonly string $firstName,)

[//]: # (    public readonly string $lastName,)

[//]: # (    #[MapTo&#40;name: 'yearOfBirth', target: DatabaseUser::class, transformer: 'transformToYear'&#41;])

[//]: # (    public readonly int $age,)

[//]: # (  &#41; {)

[//]: # (  })

[//]: # ()
[//]: # (  public static function transformToYear&#40;int $age&#41;: int)

[//]: # (  {)

[//]: # (    return &#40;new \DateTime&#40;&#41;&#41;->format&#40;'Y'&#41; - $age;)

[//]: # (  })

[//]: # (})

[//]: # (```)

[//]: # ()
[//]: # (#### Transform a property with dependencies)

[//]: # ()
[//]: # (You can use the `AutoMapper\Transformer\PropertyTransformer\PropertyTransformerInterface` to create a specific transformer.)

[//]: # (It can be useful if you need to use dependencies to transform a property.)

[//]: # ()
[//]: # (```php)

[//]: # (final readonly class UrlTransformer implements PropertyTransformerInterface)

[//]: # ({)

[//]: # (    public function __construct&#40;private UrlGenerator $urlGenerator&#41;)

[//]: # (    {)

[//]: # (    })

[//]: # ()
[//]: # (    public function transform&#40;mixed $value, object|array $source, array $context&#41;: mixed)

[//]: # (    {)

[//]: # (        return $this->urlGenerator->generate&#40;'get_resource', $value&#41;;)

[//]: # (    })

[//]: # (})

[//]: # (```)

[//]: # ()
[//]: # (You also need to register this transformer within the `AutoMapper` instance:)

[//]: # ()
[//]: # (```php)

[//]: # ($automapper = \AutoMapper\AutoMapper::create&#40;propertyTransformers: [new UrlTransformer&#40;new UrlGenerator&#40;&#41;]&#41;;)

[//]: # (```)

[//]: # ()
[//]: # (Now you can use this transformer using the `#[MapTo]` or `#[MapFrom]` attribute:)

[//]: # ()
[//]: # (```php)

[//]: # (class Resource)

[//]: # ({)

[//]: # (    public function __construct&#40;)

[//]: # (        #[MapTo&#40;'array', transformer: UrlTransformer::class&#41;])

[//]: # (        public string $id,)

[//]: # (    &#41; {)

[//]: # (    })

[//]: # (})

[//]: # (```)

[//]: # ()
[//]: # (#### Using transformer for multiple properties)

[//]: # ()
[//]: # (If you always have the same behavior for transforming properties, i.e. all id fields must be urls, you can also use the)

[//]: # (`AutoMapper\Transformer\PropertyTransformer\PropertyTransformerSupportInterface` to automatically register the transformer.)

[//]: # ()
[//]: # (```php)

[//]: # (final readonly class UrlTransformer implements PropertyTransformerInterface, PropertyTransformerSupportInterface)

[//]: # ({)

[//]: # (    public function __construct&#40;private UrlGenerator $urlGenerator&#41;)

[//]: # (    {)

[//]: # (    })

[//]: # (    )
[//]: # (    public function supports&#40;TypesMatching $types, SourcePropertyMetadata $source, TargetPropertyMetadata $target, MapperMetadata $mapperMetadata&#41;: bool)

[//]: # (    {)

[//]: # (        // Transform to url every property named `id` which is mapped to an array and where the source type can only be a string)

[//]: # (        $sourceType = $types->getSourceUniqueType&#40;&#41;;)

[//]: # (        )
[//]: # (        if &#40;$sourceType === null&#41; {)

[//]: # (            return false;)

[//]: # (        })

[//]: # (        )
[//]: # (        return $sourceType->getBuiltinType&#40;&#41; === 'string' && $mapperMetadata->target === 'array' && $source->name === 'id';)

[//]: # (    })

[//]: # ()
[//]: # (    public function transform&#40;mixed $value, object|array $source, array $context&#41;: mixed)

[//]: # (    {)

[//]: # (        return $this->urlGenerator->generate&#40;'get_resource', $value&#41;;)

[//]: # (    })

[//]: # (})

[//]: # (```)

[//]: # ()
[//]: # (By doing this you don't need to specify the transformer in the `#[MapTo]` or `#[MapFrom]` attribute for every property.)

[//]: # (If an attribute is specified for a property, it will not evaluate the `supports` method of the transformer.)

[//]: # ()
[//]: # (Please note that `supports` method is only called when creating the Mapper, not on runtime, if you use a service inside)

[//]: # (this method it will not be called each time you map a property.)
