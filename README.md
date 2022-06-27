# A simple, drop-in drafts/revisions system for Laravel models

[![Latest Version on Packagist](https://img.shields.io/packagist/v/oddvalue/laravel-drafts.svg?style=flat-square)](https://packagist.org/packages/oddvalue/laravel-drafts)
[![GitHub Tests Action Status](https://img.shields.io/github/workflow/status/oddvalue/laravel-drafts/run-tests?label=tests)](https://github.com/oddvalue/laravel-drafts/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/workflow/status/oddvalue/laravel-drafts/Check%20&%20fix%20styling?label=code%20style)](https://github.com/oddvalue/laravel-drafts/actions?query=workflow%3A"Check+%26+fix+styling"+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/oddvalue/laravel-drafts.svg?style=flat-square)](https://packagist.org/packages/oddvalue/laravel-drafts)

## Installation

You can install the package via composer:

```bash
composer require oddvalue/laravel-drafts
```

You can publish the config file with:

```bash
php artisan vendor:publish --tag="laravel-drafts-config"
```

This is the contents of the published config file:

```php
return [
];
```

## Usage

```php
$laravelDrafts = new Oddvalue\LaravelDrafts();
echo $laravelDrafts->echoPhrase('Hello, Oddvalue!');
```

## Testing

```bash
composer test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](https://github.com/spatie/.github/blob/main/CONTRIBUTING.md) for details.

## Security Vulnerabilities

Please review [our security policy](../../security/policy) on how to report security vulnerabilities.

## Credits

- [jim](https://github.com/oddvalue)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
