# Cache

AutoMapper can uses a cache system to store each Mapper generated for a specific `source` and `target` class. 
This way, the Mapper is generated only once and reused for each mapping.

By default, it will evaluate the generated Mapper, new call inside the same request or cli command will not regenerate the Mapper.

However, next request or cli command will regenerate the Mapper.

To avoid regenerating the Mapper, you can use the `cacheDirectory` option in the `AutoMapper::create()` method.

```php
use AutoMapper\AutoMapper;

$autoMapper = AutoMapper::create(cacheDirectory: '/path/to/cache');
```

This way, the Mapper will be stored in the `/path/to/cache` directory and reused for each mapping. However, if you change
the `source` or `target` class, the Mapper will be regenerated.

> [!WARNING]
> Some changes may not be detected by the cache system, like changing a dependency used by the source or target class.
> You may need to clean it manually.
> 
> However, we try our best to detect those changes, if you encounter a problem, please open an issue on the GitHub repository.
