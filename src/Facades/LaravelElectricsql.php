<?php

declare(strict_types=1);

namespace Matfire\LaravelElectricsql\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \Matfire\LaravelElectricsql\LaravelElectricsql
 */
class LaravelElectricsql extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \Matfire\LaravelElectricsql\LaravelElectricsql::class;
    }
}
