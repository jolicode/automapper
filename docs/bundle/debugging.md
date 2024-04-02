# Debug a Mapper

AutoMapper provides 2 ways to debug what's going on with a mapper when using the Symfony bundle:

## The `debug:mapper` Command

The `debug:mapper` command will display the mapping information for a specific mapper. 
This can be useful to understand how AutoMapper is mapping your objects and why some properties are not mapped.

```bash
php bin/console debug:mapper User UserDTO
```

![Profiler](../images/debug-cli.png)

## Using the symfony profiler

AutoMapper provides a panel in the Symfony profiler that will display the mapping information for each request. 
Please note that this only display Mapper that has been generated during the request, if you have a mapper that was not
generated during the request it will not be displayed.

You can find the panel in the Symfony profiler under the `AutoMapper` tab.

![Profiler](../images/debug-profiler-1.png)
![Profiler](../images/debug-profiler-2.png)