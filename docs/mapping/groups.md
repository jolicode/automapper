# Groups

In addition to use the Symfony Serializer `#[Groups]` attribute, you can also use the `#[MapTo]` and `#[MapFrom]` 
attributes to define groups of properties that should be mapped.

```php
class Source
{
    #[MapTo(target: 'array', groups: ['group1', 'group2'])]
    public $groupedProperty;
}
```

When doing so the property will be mapped only if the context contains at least one group defined in the `groups` argument.

### Cumulative groups

When using both groups from the Symfony Serializer `#[Groups]` attribute and the `groups` argument from the `#[MapTo]` 
or `#[MapFrom]` attributes, the latter groups will override the former groups.

```php
use Symfony\Component\Serializer\Attribute\Groups;

class Source
{
    #[Groups(['group1', 'group2'])]
    #[MapTo(target: 'array', groups: ['group3'])]
    public $groupedProperty;
}
```

In this case the property will be mapped only if the context contains the `group3` group.
