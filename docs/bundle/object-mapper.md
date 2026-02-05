# Object Mapper integration

> [!WARNING]
> The object mapper integration is in a experimental state, and may change in the future.
> Some behavior may not be handled correctly, and some features may not be implemented.
>
> If you find a bug or missing feature, please report it on the [issue tracker](https://github.com/jolicode/automapper/issues).

This bundle provides a way to integrate with [Object Mapper Component of Symfony](https://symfony.com/doc/current/object_mapper.html) 
by reading the mapping metadata from the Object Mapper `#[Map]` attributes. and also by providing an
implementation of the `Symfony\Component\ObjectMapper\ObjectMapperInterface` interface using the 
AutoMapper library.

You have to enable the `object_mapper` option in the configuration to use this feature.
