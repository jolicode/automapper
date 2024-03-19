# Welcome 👋

Welcome to the AutoMapper documentation, this library solves a simple problem: removing all the code you need to
map one object to another. A boring code to write and often replaced by less-performant alternatives like Symfony's
Serializer.

AutoMapper uses a convention-based matching algorithm to match up source to destination values. AutoMapper is geared 
towards model projection scenarios to flatten complex object models to DTOs and other simple objects, whose design is 
better suited for serialization, communication, messaging, or simply an anti-corruption layer between the domain and 
application layer.

## Quick start 🚀

### What is the AutoMapper ? 🤔

The AutoMapper is a anything to anything mapper, you can make either arrays, objects or array of objects and output the 
same. Mapping works by transforming an input object of one type into an output object of a different or same type (in 
case of deep copy). What makes AutoMapper interesting is that it provides some interesting conventions to take the dirty 
work out of figuring out how to map type A to type B and it has a strong aim on performance by generating the mappers 
whenever you require it. As long as type B follows AutoMapper’s established convention, almost zero configuration is 
needed to map two types.

### Why should I use it ? 🙋

Mapping code is boring. And in PHP, you often replace that by using Symfony's Serializer because you don't want to do
it by hand. We were doing the same but performance made it not possible anymore. The AutoMapper replaces the Serializer 
to do the same output by generating PHP code so it's like your wrote the mappers yourself.

The real question may be “why use object-object mapping?” Mapping can occur in many places in an application, but 
mostly in the boundaries between layers, such as between the UI/Domain layers, or Service/Domain layers. Concerns of 
one layer often conflict with concerns in another, so object-object mapping leads to segregated models, where concerns 
for each layer can affect only types in that layer.

### Installation 📦

```shell
composer require jolicode/automapper
```

### How to use it ? 🕹️

First, you need both a source and destination type to work with. The destination type’s design can be influenced by the 
layer in which it lives, but the AutoMapper works best as long as the names of the members match up to the source 
type’s members. If you have a source member called "firstName", this will automatically be mapped to a destination 
member with the name "firstName".

```php
class InputUser
{
  public function __construct(
    public readonly string $firstName,
    public readonly string $lastName,
    public readonly int $age,
  ) {
  }
}

class DatabaseUser
{
  public function __construct(
    #[ORM\Column]
    public string $firstName,
    #[ORM\Column]
    public string $lastName,
    #[ORM\Column]
    public int $age,
  ) {
  }
}

$automapper = \AutoMapper\AutoMapper::create();
dump($automapper->map(new InputUser('John', 'Doe', 28), DatabaseUser::class));

// ^ DatabaseUser^ {#1383
//   +firstName: "John"
//   +lastName: "Doe"
//   +age: 28
// }
```

### How to customize the mapping? 🚀

The mapping process could be extended in multiple ways.

#### Using the `#[MapTo]` attribute

You can use the `#[MapTo]` attribute to specify the target name of a property. This is useful when the property name is 
different in the source and target classes.

```php
class InputUser
{
  public function __construct(
    public readonly string $firstName,
    public readonly string $lastName,
    #[MapTo(name: 'userAge')]
    public readonly int $age,
  ) {
  }
}
```

This will map the `age` property to the `userAge` property in the all the target classes. If you want to map the `age`
to a specific target class, you can use the `#[MapTo]` target argument to specify the target class.

```php
class InputUser
{
  public function __construct(
    public readonly string $firstName,
    public readonly string $lastName,
    #[MapTo(name: 'userAge', target: DatabaseUser::class)]
    public readonly int $age,
  ) {
  }
}
```

#### Ignoring a field

You can ignore a field by using the `#[MapTo]` attribute.

```php
class InputUser
{
  public function __construct(
    public readonly string $firstName,
    public readonly string $lastName,
    #[MapTo(ignore: true, target: DatabaseUser::class)]
    public readonly int $age,
  ) {
  }
}
```

Ignoring a field can be useful when mapping a field to an array, as by default the `#[MapTo]` attribute with a specific
name will add the field to the mapping but does not replace it, by example:

```php
class InputUser
{
  public function __construct(
    public readonly string $firstName,
    public readonly string $lastName,
    #[MapTo(name: 'userAge', target: 'array')]
    public readonly int $age,
  ) {
  }
}
```

When mapping a `InputUser` to an array, it will have both `age` and `userAge` fields. You can ignore the `age` field,
by using the `#[MapTo]` attribute:

```php
class InputUser
{
  public function __construct(
    public readonly string $firstName,
    public readonly string $lastName,
    #[MapTo(name: 'userAge', target: 'array')]
    #[MapTo(ignore: true, target: 'array')]
    public readonly int $age,
  ) {
  }
}
```

#### Using the `#[MapFrom]` attribute

You can use the `#[MapFrom]` attribute to specify the source name of a property. It works the same way as the `#[MapTo]`
attribute but for the source class. This is useful when the source is an array or a `stdClass` object.

```php
class InputUser
{
  public function __construct(
    public readonly string $firstName,
    public readonly string $lastName,
    #[MapFrom(name: 'userAge', source: 'array')]
    public readonly int $age,
  ) {
  }
}
```

#### Using a custom transformer

You can use a custom transformer to transform the value of a property. This is useful when you need to transform the
value of a property before mapping it to the target class.

```php
class InputUser
{
  public function __construct(
    public readonly string $firstName,
    public readonly string $lastName,
    #[MapTo(name: 'yearOfBirth', target: DatabaseUser::class, transformer: 'transformToYear')]
    public readonly int $age,
  ) {
  }

  public static function transformToYear(int $age): int
  {
    return (new \DateTime())->format('Y') - $age;
  }
}
```

#### Transform a property with dependencies

You can use the `AutoMapper\Transformer\PropertyTransformer\PropertyTransformerInterface` to create a specific transformer.
It can be useful if you need to use dependencies to transform a property.

```php
final readonly class UrlTransformer implements PropertyTransformerInterface
{
    public function __construct(private UrlGenerator $urlGenerator)
    {
    }

    public function transform(mixed $value, object|array $source, array $context): mixed
    {
        return $this->urlGenerator->generate('get_resource', $value);
    }
}
```

You also need to register this transformer within the `AutoMapper` instance:

```php
$automapper = \AutoMapper\AutoMapper::create(propertyTransformers: [new UrlTransformer(new UrlGenerator()]);
```

Now you can use this transformer using the `#[MapTo]` or `#[MapFrom]` attribute:

```php
class Resource
{
    public function __construct(
        #[MapTo('array', transformer: UrlTransformer::class)]
        public string $id,
    ) {
    }
}
```

#### Using transformer for multiple properties

If you always have the same behavior for transforming properties, i.e. all id fields must be urls, you can also use the
`AutoMapper\Transformer\PropertyTransformer\PropertyTransformerSupportInterface` to automatically register the transformer.

```php
final readonly class UrlTransformer implements PropertyTransformerInterface, PropertyTransformerSupportInterface
{
    public function __construct(private UrlGenerator $urlGenerator)
    {
    }
    
    public function supports(TypesMatching $types, SourcePropertyMetadata $source, TargetPropertyMetadata $target, MapperMetadata $mapperMetadata): bool
    {
        // Transform to url every property named `id` which is mapped to an array and where the source type can only be a string
        $sourceType = $types->getSourceUniqueType();
        
        if ($sourceType === null) {
            return false;
        }
        
        return $sourceType->getBuiltinType() === 'string' && $mapperMetadata->target === 'array' && $source->name === 'id';
    }

    public function transform(mixed $value, object|array $source, array $context): mixed
    {
        return $this->urlGenerator->generate('get_resource', $value);
    }
}
```

By doing this you don't need to specify the transformer in the `#[MapTo]` or `#[MapFrom]` attribute for every property.
If an attribute is specified for a property, it will not evaluate the `supports` method of the transformer.

Please note that `supports` method is only called when creating the Mapper, not on runtime, if you use a service inside
this method it will not be called each time you map a property.
