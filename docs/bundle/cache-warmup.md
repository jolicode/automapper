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
