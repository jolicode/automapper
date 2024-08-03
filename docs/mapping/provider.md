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
