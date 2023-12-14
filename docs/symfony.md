# Symfony

To make Symfony's users life easier, we made a bundle that will make all DependencyInjection for you.

## Quick start 🚀

### Installation 📦

```shell
composer require jolicode/automapper-bundle
```

### Configuration 🔧

To use it, you just have to add the main bundle class to your `config/bundles.php` file.
```php
return [
    // ...
    AutoMapper\Bundle\AutoMapperBundle::class => ['all' => true],
];
```

Then configure the bundle to your needs, for example:
```yaml
automapper:
  autoregister: true
  mappings:
    - source: AutoMapper\Bundle\Tests\Fixtures\User
      target: AutoMapper\Bundle\Tests\Fixtures\UserDTO
      pass: DummyApp\UserConfigurationPass
```

Possible properties:
- `normalizer` (default: `false`):  A boolean which indicate if we inject the AutoMapperNormalizer;
- `cache_dir` (default: `%kernel.cache_dir%/automapper`): This setting allows you to customize the output directory 
for generated mappers;
- `mappings`: This option allows you to customize Mapper metadata, you have to specify `source` & `target` data types 
and related configuration using `pass` field. This configuration should implements `AutoMapper\Bundle\Configuration\MapperConfigurationInterface`.
- `allow_readonly_target_to_populate` (default: `false`): Will throw an exception if you use a readonly class as target 
to populate if set to `false`.

### MapperConfigurationInterface

You can add some metadata to customize a transformation. Here is an example:

```php
class UserMapperConfiguration implements MapperConfigurationInterface
{
    public function getSource(): string
    {
        return User::class;
    }

    public function getTarget(): string
    {
        return UserDTO::class;
    }

    public function process(MapperGeneratorMetadataInterface $metadata): void
    {
        $metadata->forMember('yearOfBirth', function (User $user) {
            return ((int) date('Y')) - ((int) $user->age);
            return ((int) date('Y')) - ((int) $user->age);
        });
    }
}
```

Here you have to inherit the `MapperConfigurationInterface` interface, that ways it will autoconfigure your 
transformation for a given source & target. And in the `process` method you will add or overwrite fields to map.
The fields you give onto the `forMember` method are target fields and the callback will always give you the source 
object.

### Normalizer Bridge 🌁
A Normalizer Bridge is available, aiming to be 100% feature compatible with the ObjectNormalizer of the 
``symfony/serializer`` component. The goal of this bridge **is not to replace the ObjectNormalizer** but rather 
providing a very fast alternative.

As shown in the benchmark above, using this bridge leads up to more than 8x speed increase in normalization.
