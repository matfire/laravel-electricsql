<?php

declare(strict_types=1);

use Illuminate\Http\Client\Request;
use Illuminate\Routing\Route as RoutingRoute;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Matfire\LaravelElectricsql\ElectricController;
use Matfire\LaravelElectricsql\Facades\LaravelElectricsql as LaravelElectricsqlFacade;
use Matfire\LaravelElectricsql\LaravelElectricsql;
use Matfire\LaravelElectricsql\LaravelElectricsqlServiceProvider;

beforeEach(function () {
    config()->set([
        'electricsql.electric_url' => 'https://electric.test',
        'electricsql.electric_secret' => null,
        'electricsql.electric_insecure' => true,
    ]);
});

it('resolves the same singleton from the container', function () {
    $instance = app(LaravelElectricsql::class);

    expect($instance)
        ->toBeInstanceOf(LaravelElectricsql::class)
        ->toBe(app(LaravelElectricsql::class));
});

it('resolves the singleton through the facade', function () {
    expect(LaravelElectricsqlFacade::getFacadeRoot())
        ->toBe(app(LaravelElectricsql::class));
});

it('merges the package config', function () {
    expect(config()->has('electricsql.electric_url'))->toBeTrue();
});

it('requires a valid HTTP or HTTPS Electric URL', function (mixed $url) {
    config()->set('electricsql.electric_url', $url);

    expect(fn () => new LaravelElectricsql)
        ->toThrow(InvalidArgumentException::class, 'ELECTRIC_URL must be a valid HTTP or HTTPS URL');
})->with([
    'missing URL' => null,
    'empty URL' => '',
    'URL without a scheme' => 'electric.test',
    'unsupported scheme' => 'ftp://electric.test',
]);

it('requires ELECTRIC_INSECURE to be a boolean', function () {
    config()->set('electricsql.electric_insecure', 'false');

    expect(fn () => new LaravelElectricsql)
        ->toThrow(InvalidArgumentException::class, 'ELECTRIC_INSECURE must be a boolean');
});

it('registers an electric get route with additional parameters', function () {
    $route = Route::electric('/electric', ['table' => 'users']);

    expect($route)
        ->toBeInstanceOf(RoutingRoute::class)
        ->and($route->methods())->toBe(['GET', 'HEAD'])
        ->and($route->getActionName())->toBe(ElectricController::class)
        ->and($route->getAction('electric_parameters'))->toBe(['table' => 'users']);
});

it('proxies electric responses while filtering query parameters', function () {
    Http::preventStrayRequests();
    Http::fake([
        'electric.test/v1/shape*' => Http::response(
            body: '{"data":[]}',
            status: 200,
            headers: [
                'content-type' => 'application/json',
                'electric-handle' => 'handle-123',
                'transfer-encoding' => 'chunked',
            ],
        ),
    ]);

    Route::electric('/electric/users', ['table' => 'users']);

    $response = $this->get('/electric/users?live=true&offset=42&unsupported=value');

    $response
        ->assertOk()
        ->assertContent('{"data":[]}')
        ->assertHeader('content-type', 'application/json')
        ->assertHeader('electric-handle', 'handle-123')
        ->assertHeaderMissing('transfer-encoding');

    Http::assertSent(function (Request $request): bool {
        parse_str(parse_url($request->url(), PHP_URL_QUERY) ?? '', $query);

        return str_starts_with($request->url(), 'https://electric.test/v1/shape?')
            && $query['table'] === 'users'
            && $query['live'] === 'true'
            && $query['offset'] === '42'
            && ! array_key_exists('unsupported', $query);
    });
});

it('lets route parameters override request query parameters', function () {
    Http::preventStrayRequests();
    Http::fake([
        'electric.test/v1/shape*' => Http::response(),
    ]);

    Route::electric('/electric/items', [
        'table' => 'items',
        'offset' => '100',
    ]);

    $this->get('/electric/items?offset=1')->assertOk();

    Http::assertSent(function (Request $request): bool {
        parse_str(parse_url($request->url(), PHP_URL_QUERY) ?? '', $query);

        return $query['table'] === 'items'
            && $query['offset'] === '100';
    });
});

it('propagates an upstream error response', function () {
    Http::preventStrayRequests();
    Http::fake([
        'electric.test/v1/shape*' => Http::response(
            body: 'Electric is unavailable',
            status: 503,
            headers: ['electric-error' => 'service-unavailable'],
        ),
    ]);

    Route::electric('/electric/error', ['table' => 'items']);

    $this->get('/electric/error')
        ->assertStatus(503)
        ->assertContent('Electric is unavailable')
        ->assertHeader('electric-error', 'service-unavailable');
});

it('requires a secret when insecure mode is disabled', function () {
    config()->set([
        'electricsql.electric_insecure' => false,
        'electricsql.electric_secret' => null,
    ]);

    expect(fn () => new LaravelElectricsql)
        ->toThrow(InvalidArgumentException::class, 'ELECTRIC_SECRET must be a non-empty string when secure mode is enabled');
});

it('rejects an invalid secret when secure mode is enabled', function (mixed $secret) {
    config()->set([
        'electricsql.electric_insecure' => false,
        'electricsql.electric_secret' => $secret,
    ]);

    expect(fn () => new LaravelElectricsql)
        ->toThrow(InvalidArgumentException::class, 'ELECTRIC_SECRET must be a non-empty string when secure mode is enabled');
})->with([
    'empty secret' => '',
    'blank secret' => '   ',
    'non-string secret' => [['secret']],
]);

it('authenticates secure electric requests with the configured secret', function () {
    config()->set([
        'electricsql.electric_insecure' => false,
        'electricsql.electric_secret' => 'test-secret',
    ]);

    Http::preventStrayRequests();
    Http::fake([
        'electric.test/v1/shape*' => Http::response(),
    ]);

    Route::electric('/electric/secure', ['table' => 'items']);

    $this->get('/electric/secure?secret=request-secret')->assertOk();

    Http::assertSent(function (Request $request): bool {
        parse_str(parse_url($request->url(), PHP_URL_QUERY) ?? '', $query);

        return $query['table'] === 'items'
            && $query['secret'] === 'test-secret';
    });
});

it('registers both config publish tags', function (string $tag) {
    $paths = ServiceProvider::pathsToPublish(
        LaravelElectricsqlServiceProvider::class,
        $tag,
    );

    expect($paths)->toHaveCount(1)
        ->and(realpath((string) array_key_first($paths)))
        ->toBe(realpath(__DIR__.'/../../config/electricsql.php'))
        ->and(array_values($paths))
        ->toBe([config_path('electricsql.php')]);
})->with([
    'combined resources tag' => 'laravel-electricsql',
    'config-only tag' => 'laravel-electricsql-config',
]);
