# Cronski Laravel Package

[![Latest Version on Packagist](https://img.shields.io/packagist/v/modstore/cronski-laravel.svg?style=flat-square)](https://packagist.org/packages/modstore/cronski-laravel)
[![Total Downloads](https://img.shields.io/packagist/dt/modstore/cronski-laravel.svg?style=flat-square)](https://packagist.org/packages/modstore/cronski-laravel)

Laravel package for https://cronski.com

## Installation

You can install the package via composer:

```bash
composer require modstore/cronski-laravel
```

```bash
php artisan vendor:publish --provider="Modstore\Cronski\CronskiServiceProvider"
```

## Usage
If you set scheduled = true in the config, add the following scheduled command to your App\Console\Kernel.php

Once you've enabled this option, you will need to run the migration to create the table for storing the pending requests.
``` php
// Send pending Cronski requests. You can reduce the frequency of this command to whatever suits.
$schedule->command('cronski:send-pending-requests')->everyMinute();
```

### Testing

``` bash
composer test
```

### Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information what has changed recently.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

### Security

If you discover any security related issues, please email mark@cronski.com instead of using the issue tracker.

## Credits

- [Mark Whitney](https://github.com/modstore)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
