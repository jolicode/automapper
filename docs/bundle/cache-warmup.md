# Caching class for production

Instead of caching metadata of a class, AutoMapper generates a class in PHP that remove all reflection calls and the
overhead of the decision process. This class is generated in the cache directory of your Symfony application.

In order to do that it needs to know which mapping you want to generate. By default, it will generate the mapper
when asked for the first time.

This may not be suited in a production environment, as it may slow down the first request or the disk may not be 
writable resulting in an error.

To avoid this problem, you can specify the mappings you want to generate in the configuration file : 

```yaml
automapper:
  mappings:
    mappers:
      - source: App\Entity\User
        target: App\Api\DTO\User
    
      - source: App\Entity\User
        target: array
        reverse: true
```

Then when running the `cache:warmup` command, this will generate the mappers for you.

> [!NOTE]
> When a mapping have dependencies, it will generate the dependencies as well even if not specified in the mappings configuration.

This way, you can generate all the mappers you need before deploying your application.

## Automatically register mappers

You can also define a list of paths where the mappers are located, and the bundle will automatically register them for you.

```yaml
automapper:
  mappings:
    paths:
      - "%kernel.project_dir%/src/Entity"
```

All classes in the specified paths with the `#[Mapper]` attribute will be registered in the container.

This attribute need a `source` and/or `target` argument to specify which mapping to register.

```php
#[Mapper(source: 'array', target: 'array')]
class Entity
{
    public string $foo;
}
```

This will generate a mapper from `array` to `Entity` and vice versa during the cache warmup.

You can also specify an array of sources and / or targets to generate multiple mappers at once.

```php
#[Mapper(source: 'array', target: ['array', EntityDto::class])]
class Entity
{
    public string $foo;
}
```
