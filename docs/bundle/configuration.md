# Configuring the Bundle

By default the bundle is configured to work out of the box, but you can customize it to your needs.

Create a configuration file in your Symfony application, for example `config/packages/automapper.yaml`.

Then configure the bundle to your needs, for example:

```yaml
automapper:
  class_prefix: "Symfony_Mapper_"
  constructor_strategy: 'auto'
  date_time_format: !php/const:DateTimeInterface::RFC3339
  check_attributes: true
  auto_register: true
  map_private_properties: true
  allow_readonly_target_to_populate: false
  normalizer:
    enabled: false
    only_registered_mapping: false
    priority: 1000
  loader:
    eval: false
    cache_dir: "%kernel.cache_dir%/automapper"
    reload_strategy: "always"
  serializer_attributes: true
  api_platform: false
  name_converter: null
  mapping:
    paths:
      - "%kernel.project_dir%/src/Entity"
    mappers:
      - source: AutoMapper\Bundle\Tests\Fixtures\User
        target: AutoMapper\Bundle\Tests\Fixtures\UserDTO
        reverse: false
```

## Configuration Reference

* `class_prefix` (default: `Symfony_Mapper_`): The prefix to use for the generated mappers class names;
* `constructor_strategy` (default: `auto`): If the generated mapper should use the constructor to instantiate the target. 
    * When set to `auto`, AutoMapper will use the constructor if all mandatory properties are present in the source object, 
      otherwise it will not use it.
    * When set to `never`, AutoMapper will never use the constructor.
    * When set to `always`, AutoMapper will always use the constructor, even if some mandatory properties are missing. 
      In this case you may need to [provide a default value for the missing properties using the context](../getting-started/context.md).

* `date_time_format` (default: `\DateTimeInterface::RFC3339`): The format to use to transform a date from/to a string,
  can be overwritten by the attributes thanks to `dateTimeFormat` property, see [DateTime format](../mapping/date-time.md)
  for more details about it;
* `check_attributes` (default: `true`): Check if the field should be mapped at runtime, this allow you to have dynamic
  partial mapping, if you don't use this feature set it to false as it will improve the performance;
* `auto_register` (default: `true`): If the bundle should auto register the mappers in the container when it does not
  exist, when set to `false` you have to register the mappers manually using the `mapping` option, this option is useful
  when you cannot write to the disk, and you want to use the cache warmup;
* `map_private_properties` (default: `true`): If the mapper should map private properties;
* `allow_readonly_target_to_populate` (default: `false`): Will throw an exception if you use a readonly class as target
  to populate if set to `false`.
* `normalizer`:  Configure how the normalizer should behave;
    * `enabled` (default: `false`): If the normalizer should be enabled;
    * `only_registered_mapping` (default: `false`): If the normalizer should only use the registered mapping;
    * `priority` (default: `1000`): The priority of the normalizer, the higher the value the higher the priority;
* `loader`:
    * `eval` (default: `false`): If the loader should use the eval function to load the mappers, this is useful when
      you cannot write to the disk;
    * `cache_dir` (default: `%kernel.cache_dir%/automapper`): The directory where the loader should write the mappers;
    * `reload_strategy` (default: `always` when `%kernel.debug%` is true, `never` otherwise): The strategy to use to 
  generate the mappers between each request, can be `always`, `never` or `on_change`:
        * `always` will generate the mappers at each request;
        * `never` will generate them only if they don't exist;
        * `on_change` will generate the mappers only if the source or target class has changed since the last generation;
* `serializer_attributes` (default: `true` if the symfony/serializer is available, false otherwise): A boolean which 
indicate if we use the attribute of the symfony/serializer during the mapping, this only apply to the `#[Groups]`, 
`#[MaxDepth]`, `#[Ignore]` and `#[DiscriminatorMap]` attributes;
* `api_platform` (default: `false`): A boolean which indicate if we use services from the api-platform/core package and
inject extra data (json ld) in the mappers when we map a Resource class to or from an array.
* `name_converter` (default: `null`): A service id which implement the `AdvancedNameConverterInterface` or 
  `NameConverterInterface` (starting from 7.2) from the symfony/serializer, this name converter will be used when 
   mapping from an array to an object and vice versa;
* `mapping`: Allow to auto register the mappers for warmup, and selecting them to normalizer if wanted
    * `paths`: A list of paths where to look for mappers to register; This will automatically register all classes
      with the `#[Mapper]` attribute in the given paths.
    * `mappers`: A list of mapping to register, each mapping should have a `source` and a `target` key, and can have
      a `reverse` key to also register the reverse mapping. 
