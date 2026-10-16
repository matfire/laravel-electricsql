---
name: laravel-electricsql-development
description: Configure and use matfire/laravel-electricsql to proxy ElectricSQL shapes through Laravel. Use when adding shape routes, custom proxy controllers, or tests for ElectricSQL endpoints.
---

# Laravel ElectricSQL

This package proxies requests from Laravel to Electric's `/v1/shape`
endpoint. It provides a route macro and an injectable response service.

## Configuration

Install the package:

```bash
composer require matfire/laravel-electricsql
```

Configure the Electric server's base URL and credentials:

```dotenv
ELECTRIC_URL=http://localhost:3000
ELECTRIC_SECRET=your-server-secret
ELECTRIC_INSECURE=false
```

The package appends `/v1/shape` to `ELECTRIC_URL`.
Keep the secret on the server.

For an Electric instance running in insecure development mode,
set `ELECTRIC_INSECURE=true`; the package then omits the secret.

Configuration publishing is optional:

```bash
php artisan vendor:publish --tag=laravel-electricsql-config
```

## Shape routes

Use the route macro when the shape parameters are fixed:

```php
use Illuminate\Support\Facades\Route;

Route::electric('/electric/todos', [
    'table' => 'todos',
])->middleware('auth');
```

The macro registers a GET route and returns a Laravel route instance,
so middleware and route names can be chained normally.

Choose authentication middleware appropriate to the application.
Authentication alone does not restrict which rows a user can sync.

## Custom controllers

Use the injectable service when authorization or shape parameters
depend on the current request:

```php
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Matfire\LaravelElectricsql\LaravelElectricsql;

class TodoElectricController extends Controller
{
    public function __invoke(
        Request $request,
        LaravelElectricsql $electric,
    ): Response {
        // Authorize access and determine the permitted shape here.

        return $electric->toResponse($request, [
            'table' => 'todos',
        ]);
    }
}
```

For user- or tenant-scoped data, derive shape restrictions from
authorized server-side context before calling `toResponse`.
Do not pass `$request->all()` as shape parameters.

## Parameter and response behavior

- The package forwards an allowlist of Electric query parameters,
  including `offset`, `handle`, `live`, and supported subset parameters.
- Supply `table` and other shape-defining parameters server-side.
- Additional parameters passed to `toResponse` override forwarded
  query parameters.
- In secure mode, the configured secret overrides any supplied secret.
- The upstream body and status are returned, including error responses.
- Upstream headers are copied with `transfer-encoding` removed.
- The current implementation buffers the upstream body; it does not
  implement a streaming response.

## Testing integrations

Use Laravel's `Http::fake()` and `Http::preventStrayRequests()`
to test without a running Electric server.

Verify the application's behavior:

- Unauthorized requests cannot access protected shapes.
- Authorized requests select the expected table and row restrictions.
- Client query parameters cannot override server-controlled restrictions.
- Upstream failures retain their status and response body.
