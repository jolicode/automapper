# Inheritance Mapping

A `source` or `target` class may inherit from another class. 

When creating the mapping, AutoMapper can determine the correct mapping by using the inheritance information from
the `#[Mapper]` attribute.

```php
#[Mapper(discriminator: new Discriminator(
    mapping: [
        DogDto::class => Dog::class,
        CatDto::class => Cat::class,
    ]
))]
abstract class Pet
{
    /** @var string */
    public $name;

    /** @var PetOwner */
    public $owner;
}
```

When mapping a `Pet` object, AutoMapper will automatically determine the correct class to instantiate based on 
the instance of the property.

If it's a `Dog` class it will map to a `DogDto` class, and if it's a `Cat` class it will map to a `CatDto` class.

Note that the key is the `target` class, and the value is the `source` class.

## Mapping to an array

When mapping to an array there is no class to determine the correct mapping. In this case, instead of using the instance 
of the object, AutoMapper will use the value of a specific property to determine the correct mapping.

```php
#[Mapper(target: 'array', discriminator: new Discriminator(
    property: 'type',
    mapping: [
        'dog' => Dog::class,
        'cat' => Cat::class,
    ]
))]
abstract class Pet
{
    /** @var string */
    public $name;

    /** @var PetOwner */
    public $owner;
}
```

In this example, when mapping to / from an array, AutoMapper will write / read the `type` property to determine 
the correct mapping.

If the `type` property is equal to `dog`, it will map to / from the `Dog` class, and if it's equal to `cat`,
it will map to / from the `Cat` class.

> [!NOTE]
> It also possible to use the same principle when mapping to a data structure that don't have inheritance.
