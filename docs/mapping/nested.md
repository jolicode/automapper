# Nested properties

The `#[MapTo]` and `#[MapFrom]` attributes support mapping to and from nested properties using dot notation in the `property` parameter
of the attributes.

```php
class UserDto
{
    #[MapTo(property: 'address.street')]
    public string $streetAddress;

    #[MapTo(property: 'address.city')]
    public string $cityAddress;
    
    public string $name;
}

class User
{
    public Address $address;
    public string $name;
    
    public function __construct()
    {
        $this->address = new Address();
    }
}

class Address
{
    public string $street;
    public string $city;
}
```

When mapping from `UserDto` to `User`, the `streetAddress` and `cityAddress` properties will be mapped to the `street` and `city` properties of the nested `address` property in the `User` class.

```php
$mapper->map(new UserDto(
    streetAddress: '123 Main St',
    cityAddress: 'Springfield',
    name: 'John Doe'
), User::class);
```

This will result in a `User` object with an `Address` object where `street` is '123 Main St' and `city` is 'Springfield', and `name` is 'John Doe'.

It can also works in the opposite direction when mapping from `User` to `UserDto` using the `#[MapFrom]` attribute.

> [!WARNING]
> When using nested properties, the intermediate objects (like `Address` in this case) need to be properly 
> initialized before mapping to avoid null reference errors.
