# Identifier : Mapping existing objects

With AutoMapper you can can map data to an existing object. 

```php
// We fetch existing object from database
$user = $this->entityManager->find(User::class, $id);

// We map data to existing object
$mapper->map($requestValue, $user);
```

This works fine for most cases, but you may have nested objects in your source and destination objects.

As an example, let's say your `User` object has multiple `Address` object.

```php
class User 
{
    public string $name;
    /** @var Address[] */
    public array $address;
}

class Address 
{
    public string $id;
    public string $street;
    public string $city;
}
```

When mapping data to an existing `User` object, you don't want to instantiate new `Address` objects as it may
create duplicates in your database or even worse fail with an exception because it's already existing.

For that purpose, AutoMapper support the concept of Identifier which allow to reuse existing objects in a collection base
on their identifiers.

When AutoMapper is configured to be used with an ORM like Doctrine, it will automatically use the metadata to find the identifier of your objects.

In other cases, you can manually tell AutoMapper which property to use as identifier.

```php
class Address 
{
    #[MapFrom(identifier: true)]
    public string $id;
}
```

When mapping a collection of `Address` objects, AutoMapper will now look for existing objects in the destination collection and use
them if identifiers match.

Otherwise, it will create a new object and add it to the collection.
