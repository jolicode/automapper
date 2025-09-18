# Provider

> [!WARNING]
> Providers are experimental and may change in the future.

Providers are a way to instantiate the `target` during the mapping process.

By default, the AutoMapper will try to instantiate the `target` object using the constructor, or without if not possible.
However, in some cases you may want to use a custom provider. Like fetch the object from the database, or use a factory.

In this case you can create a provider class that implements the `ProviderInterface` interface.

```php
use AutoMapper\Provider\ProviderInterface;

class MyProvider implements ProviderInterface
{
    public function provide(string $targetType, mixed $source, array $context): object|null
    {
        return new $targetType();
    }
}
```

You have to register this provider when you create the `AutoMapper` object.

```php
use AutoMapper\AutoMapper;

$autoMapper = AutoMapper::create(providers: [new MyProvider()]);
```

> [!NOTE]
> When using the Symfony Bundle version of the AutoMapper, you can use the `automapper.provider` tag to register the provider.
> If you have autoconfiguration enabled, you do not need to register the provider manually as the tag will be automatically added.

Then you can use the `#[MapProvider]` attribute on top of the `target` class that you want to use this provider.

```php
use AutoMapper\Attribute\MapProvider;

#[MapProvider(provider: MyProvider::class)]
class Entity
{
}
```

> [!NOTE]
> When using the Symfony Bundle version of the AutoMapper, the provider will be the service id, which may be different
> from the class name.

Now, every time the `AutoMapper` needs to instantiate the `Entity` class, it will use the `MyProvider` class.

If you provider return `null`, the `AutoMapper` will try to instantiate the `target` object using the constructor, or without if not possible.

> [!NOTE]
> When using the `target_to_populate` option in the `context` array, the `AutoMapper` will use this object instead of the
> one created by the provider.

### Early return

If you want to return the object from the provider without mapping the properties, you can return a 
`AutoMapper\Provider\EarlyReturn` object from the `provide` method with the object you want to return inside.

```php
use AutoMapper\Provider\EarlyReturn;
use AutoMapper\Provider\ProviderInterface;

class MyProvider implements ProviderInterface
{
    public function provide(string $targetType, mixed $source, array $context): object|null
    {
        return new EarlyReturn(new $targetType());
    }
}
```

## Third party providers

We also provide some third party providers that allow you to use popular libraries to instantiate the `target` object : 

### Doctrine 

Automatically fetch the target from the database if it's an entity.

This provider is automatically registered when : 

 * an `Doctrine\Persistence\ObjectManager` is given when creating the `AutoMapper` object or is configured in the Symfony Bundle.
 * The `target` class is managed by this `ObjectManager`.

### Api Platform

Use the Api Platform Provider to fetch the target object. It also converts IRIs to objects.

This provider is automatically registered when : 

 * The Api Platform library is installed and `api_platform` is enabled in the Symfony Bundle Configuration.
 * The `target` class is a resource managed by Api Platform.

### Disable auto registration

If you want to disable the automatic registration of the Doctrine and Api Platform providers on a specific Mapping,
you can use the `#[MapProvider]` attribute with `false`.

```php
use AutoMapper\Attribute\MapProvider;

#[MapProvider(provider: false)]
class Entity
{
}
```
