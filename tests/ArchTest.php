<?php

declare(strict_types=1);

arch()->preset()->php();

arch()->preset()->security();

arch('it will not use dd(), ddd(), env(), or exit()')
    ->expect('Matfire\LaravelElectricsql')
    ->not->toUse(['dd', 'ddd', 'env', 'exit']);

arch('the package source declares strict types')
    ->expect('Matfire\LaravelElectricsql')
    ->toUseStrictTypes();
