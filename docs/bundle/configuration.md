# Configuring the Bundle

By default the bundle is configured to work out of the box, but you can customize it to your needs.

Create a configuration file in your Symfony application, for example `config/packages/automapper.yaml`.

Then configure the bundle to your needs, for example:

```yaml
automapper:
  class_prefix: "Symfony_Mapper_"
  allow_constructor: true
  date_time_format: !php/const:DateTimeInterface::RFC3339
  check_attributes: true
  auto_register: true
  map_private_properties: true
  allow_readonly_target_to_populate: false
  normalizer: false
  serializer: true
  name_converter: null
  cache_dir: "%kernel.cache_dir%/automapper"
  mappings:
    - source: AutoMapper\Bundle\Tests\Fixtures\User
      target: AutoMapper\Bundle\Tests\Fixtures\UserDTO
```

## Configuration Reference

* `class_prefix` (default: `Symfony_Mapper_`): The prefix to use for the generated mappers class names;
* `allow_constructor` (default: `true`): If the generated mapper should use the constructor to instantiate the target.
* `date_time_format` (default: `\DateTimeInterface::RFC3339`): The format to use to transform a date from/to a string;
* `check_attributes` (default: `true`): Check if the field should be mapped at runtime, this allow you to have dynamic
  partial mapping, if you don't use this feature set it to false as it will improve the performance;
* `auto_register` (default: `true`): If the bundle should auto register the mappers in the container when it does not
  exist, when set to `false` you have to register the mappers manually using the `mapping` option, this option is useful
  when you cannot write to the disk and you want to use the cache warmup;
* `map_private_properties` (default: `true`): If the mapper should map private properties;
* `allow_readonly_target_to_populate` (default: `false`): Will throw an exception if you use a readonly class as target
  to populate if set to `false`.
* `normalizer` (default: `false`):  A boolean which indicate if we inject the AutoMapperNormalizer;
* `serializer` (default: `true` if the symfony/serializer is available, false otherwise): A boolean which indicate
  if we use the attribute of the symfony/serializer during the mapping, this only apply to the `#[Groups]`, `#[MaxDepth]`,
  `#[Ignore]` and `#[DiscriminatorMap]` attributes;
* `name_converter` (default: `null`): A service id which implement the `AdvancedNameConverterInterface` from the symfony/serializer,
  this name converter will be used when mapping from an array to an object and vice versa;
* `cache_dir` (default: `%kernel.cache_dir%/automapper`): This setting allows you to customize the output directory
  for generated mappers;
* `mappings`: This option allows you to set a list of Mapper which will be generated during the cache warmup, you have
  to specify `source` & `target` data types;
