# Inheritance Mapping

A `source` or `target` class may inherit from another class. 

When creating the mapping, AutoMapper can determine the correct mapping by using the inheritance information from
the Symfony Serializer `#[DiscriminatorMap]` attribute.

```php
#[DiscriminatorMap(typeProperty: 'type', mapping: [
    'cat' => Cat::class,
    'dog' => Dog::class,
    'fish' => Fish::class,
])]
abstract class Pet
{
    /** @var string */
    public $type;

    /** @var string */
    public $name;

    /** @var PetOwner */
    public $owner;
}
```

When mapping a `Pet` object, AutoMapper will automatically determine the correct class to instantiate based on the `type` property.

[Learn more about the Symfony Serializer inheritance mapping](https://symfony.com/doc/current/components/serializer.html#serializing-interfaces-and-abstract-classes)

> [!NOTE]
> If you don't use the Symfony Serializer we do not provide, yet, any way to determine the correct class to instantiate.
