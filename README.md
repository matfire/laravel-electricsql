<div align="center">
    <h1>Laravel Electricsql</h1>
</div>

<p align="center">
    <a href="https://packagist.org/packages/matfire/laravel-electricsql"><img src="https://img.shields.io/packagist/v/matfire/laravel-electricsql.svg?style=flat-square" alt="Packagist"></a>
    <a href="https://packagist.org/packages/matfire/laravel-electricsql"><img src="https://img.shields.io/packagist/php-v/matfire/laravel-electricsql.svg?style=flat-square" alt="PHP from Packagist"></a>
    <a href="https://packagist.org/packages/matfire/laravel-electricsql"><img src="https://badge.laravel.cloud/badge/matfire/laravel-electricsql?style=flat" alt="Laravel versions"></a>
    <a href="https://github.com/matfire/laravel-electricsql/actions"><img alt="GitHub Workflow Status (main)" src="https://img.shields.io/github/actions/workflow/status/matfire/laravel-electricsql/tests.yml?branch=main&label=Tests&style=flat-square"></a>
    <a href="https://packagist.org/packages/matfire/laravel-electricsql"><img src="https://img.shields.io/packagist/dt/matfire/laravel-electricsql.svg?style=flat-square" alt="Total Downloads"></a>
</p>

Utilities for ElectricSQL in Laravel

## Installation

You can install the package via Composer:

```bash
composer require matfire/laravel-electricsql
```

You may publish all of the package's resources at once:

```bash
php artisan vendor:publish --tag="laravel-electricsql"
```

Or, you may publish each resource individually:

### Publishing the Configuration File

```bash
php artisan vendor:publish --tag="laravel-electricsql-config"
```

## Usage

You can use `laravel-electricsql` in two ways:

### Route Macro

`laravel-electricsql` exposes a Route macro `Route::electric` that handles all the logic for you (sending the request, getting the response, stripping the headers) in a single line.

You can use it like so:

```php
Route::electric('/electric/todo', ['table' => 'todos']);
```
This will register a GET route to the specified url and pass the keyed array as extra parameters to the request.

### toResponse

If you want more control and handle some extra logic or effects in your own controller, `laravel-electricsql` also exposes the `toResponse` method. Since it's available as a singleton, you can get it in your Controller parameters and use it like so:

```php
use Matfire\LaravelElectricsql\LaravelElectricsql;

class TodoElectricController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request, LaravelElectricsql $electric)
    {
        return $electric->toResponse($request, ["table" => "todos"]);
    }
}
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Thank you for considering contributing to Laravel Electricsql! Please review our [contributing guide](.github/CONTRIBUTING.md) to get started.

## Security Vulnerabilities

Please review [our security policy](.github/SECURITY.md) on how to report security vulnerabilities.

## Credits

- [Matteo Gassend](https://github.com/matfire)
- [All Contributors](../../contributors)

## License

Laravel Electricsql is open-sourced software licensed under the [MIT license](LICENSE.md).
