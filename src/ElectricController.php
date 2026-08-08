<?php

declare(strict_types=1);

namespace Matfire\LaravelElectricsql;

use Illuminate\Http\Request;
use Illuminate\Http\Response;

class ElectricController
{
    public function __invoke(Request $request, LaravelElectricsql $electric): Response
    {
        $additionalParams = $request->route()
            ?->getAction('electric_parameters') ?? [];

        return $electric->toResponse($request, $additionalParams);
    }
}
