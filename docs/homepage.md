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