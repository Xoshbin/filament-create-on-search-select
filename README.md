# This is my package filament-create-on-search-select

[![Latest Version on Packagist](https://img.shields.io/packagist/v/xoshbin/filament-create-on-search-select.svg?style=flat-square)](https://packagist.org/packages/xoshbin/filament-create-on-search-select)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/xoshbin/filament-create-on-search-select/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/xoshbin/filament-create-on-search-select/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/actions/workflow/status/xoshbin/filament-create-on-search-select/fix-php-code-style-issues.yml?branch=main&label=code%20style&style=flat-square)](https://github.com/xoshbin/filament-create-on-search-select/actions?query=workflow%3A"Fix+PHP+code+styling"+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/xoshbin/filament-create-on-search-select.svg?style=flat-square)](https://packagist.org/packages/xoshbin/filament-create-on-search-select)



This is where your description should go. Limit it to a paragraph or two. Consider adding a small example.

## Installation

You can install the package via composer:

```bash
composer require xoshbin/filament-create-on-search-select
```

You can publish and run the migrations with:

```bash
php artisan vendor:publish --tag="filament-create-on-search-select-migrations"
php artisan migrate
```

You can publish the config file with:

```bash
php artisan vendor:publish --tag="filament-create-on-search-select-config"
```

Optionally, you can publish the views using

```bash
php artisan vendor:publish --tag="filament-create-on-search-select-views"
```

This is the contents of the published config file:

```php
return [
];
```

## Usage

```php
$filamentCreateOnSearchSelect = new Xoshbin\FilamentCreateOnSearchSelect();
echo $filamentCreateOnSearchSelect->echoPhrase('Hello, Xoshbin!');
```

## Testing

```bash
composer test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](.github/CONTRIBUTING.md) for details.

## Security Vulnerabilities

Please review [our security policy](../../security/policy) on how to report security vulnerabilities.

## Credits

- [Khoshbin](https://github.com/Xoshbin)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
