<?php

declare(strict_types=1);

namespace Matfire\LaravelElectricsql\Tests;

use Matfire\LaravelElectricsql\LaravelElectricsqlServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [
            LaravelElectricsqlServiceProvider::class,
        ];
    }
}
