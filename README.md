<h1 align="center">
  <a href="https://github.com/jolicode/automapper"><img src="https://jolicode.com/media/original/oss/headers/automapper.png" alt="automapper"></a>
  <br />
  AutoMapper
  <br />
  <sub><em><h6>A blazing fast Data Mapper for PHP.</h6></em></sub>
</h1>

<!-- Quelques badges optionnels, cool pour l'open source -->
<div align="center">

[![PHP Version Require](http://poser.pugx.org/jolicode/automapper/require/php)](https://packagist.org/packages/jolicode/automapper)<!-- Attention automapper -->
[![Monthly Downloads](http://poser.pugx.org/jolicode/automapper/d/monthly)](https://packagist.org/packages/jolicode/automapper)

</div>

The AutoMapper solves a simple problem: removing all the code you need to map one object to another. A boring code to
write and often replaced by less-performant alternatives like Symfony's Serializer.

## Quick Start 🚀

1. Install:

```shell
composer require jolicode/automapper
```

2. Use it:

```php
$mapper = AutoMapper::create();
$target = $mapper->map($data, Target::class);
```

You can read more about this library and how to use it on the [documentation](https://jolicode.github.io/automapper/).

## Support

For support, please create an issue on [Github tracker](https://github.com/jolicode/automapper/issues)

<br><br>
<div align="center">
<a href="https://jolicode.com/"><img src="https://jolicode.com/media/original/oss/footer-github.png?v3" alt="JoliCode is sponsoring this project"></a>
</div>
