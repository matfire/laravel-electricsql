<?php

declare(strict_types=1);

namespace Matfire\LaravelElectricsql;

use Illuminate\Routing\Route as RoutingRoute;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class LaravelElectricsqlServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/electricsql.php', 'electricsql');

        $this->app->singleton(LaravelElectricsql::class);
        $this->registerRouterMacro();
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if (! $this->app->runningInConsole()) {
            return;
        }

        $this->publishes([
            __DIR__.'/../config/electricsql.php' => config_path('electricsql.php'),
        ], ['laravel-electricsql', 'laravel-electricsql-config']);
    }

    protected function registerRouterMacro(): void
    {
        Router::macro('electric', /**
         * @param  array<string, mixed>  $params
         */ function (string $uri, array $params = []): RoutingRoute {
            return tap(Route::get($uri, ElectricController::class), function (RoutingRoute $route) use ($params): void {
                $route->setAction([...$route->getAction(), 'electric_parameters' => $params]);
            });
        });
    }
}
