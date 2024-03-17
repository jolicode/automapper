# Symfony

To make Symfony's users life easier, we made a bundle that will make all DependencyInjection for you.

## Quick start 🚀

### Installation 📦

The bundle is already shipped with the main package, so you don't have to install it manually.

### Configuration 🔧

To use it, you have to add the main bundle class to your `config/bundles.php` file.
```php
return [
    // ...
    AutoMapper\Symfony\Bundle\AutoMapperBundle::class => ['all' => true],
];
```

Then configure the bundle to your needs, for example:
```yaml
automapper:
  normalizer: true
  serializer: false
  mappings:
    - source: AutoMapper\Bundle\Tests\Fixtures\User
      target: AutoMapper\Bundle\Tests\Fixtures\UserDTO
  allow_readonly_target_to_populate: false
```

#### Reference

 * `normalizer` (default: `false`):  A boolean which indicate if we inject the AutoMapperNormalizer;
 * `serializer` (default: `true` if the symfony/serializer is available, false otherwise): A boolean which indicate 
if we use the attribute of the symfony/serializer during the mapping, this only apply to the `#[Groups]`, `#[MaxDepth]`, 
`#[Ignore]` and `#[DiscriminatorMap]` attributes;
 * `name_converter` (default: `null`): A service id which implement the `AdvancedNameConverterInterface` from the symfony/serializer,
this name converter will be used when mapping from an array to an object and vice versa;
 * `cache_dir` (default: `%kernel.cache_dir%/automapper`): This setting allows you to customize the output directory 
for generated mappers;
 * `mappings`: This option allows you to set a list of Mapper which will be generated during the cache warmup, you have 
to specify `source` & `target` data types.
 * `allow_readonly_target_to_populate` (default: `false`): Will throw an exception if you use a readonly class as target 
to populate if set to `false`.

### Normalizer Bridge 🌁

A Normalizer Bridge is available, aiming to be 100% feature compatible with the ObjectNormalizer of the 
``symfony/serializer`` component. The goal of this bridge **is not to replace the ObjectNormalizer** but rather 
providing a very fast alternative.

As shown in the benchmark above, using this bridge leads up to more than 8x speed increase in normalization.
