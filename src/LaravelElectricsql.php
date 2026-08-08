<?php

declare(strict_types=1);

namespace Matfire\LaravelElectricsql;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use InvalidArgumentException;

class LaravelElectricsql
{
    public function __construct()
    {
        $url = config('electricsql.electric_url');

        if (
            ! is_string($url)
            || filter_var($url, FILTER_VALIDATE_URL) === false
            || ! in_array(parse_url($url, PHP_URL_SCHEME), ['http', 'https'], true)
        ) {
            throw new InvalidArgumentException('ELECTRIC_URL must be a valid HTTP or HTTPS URL.');
        }

        $insecure = config('electricsql.electric_insecure');

        if (! is_bool($insecure)) {
            throw new InvalidArgumentException('ELECTRIC_INSECURE must be a boolean.');
        }

        $secret = config('electricsql.electric_secret');

        if (! $insecure && (! is_string($secret) || trim($secret) === '')) {
            throw new InvalidArgumentException('ELECTRIC_SECRET must be a non-empty string when secure mode is enabled.');
        }
    }

    /** @var list<string> */
    private array $query_params = [
        'live',
        'live_sse',
        'experimental_live_sse',
        'handle',
        'offset',
        'cursor',
        'expired_handle',
        'log',
        'subset__where',
        'subset__limit',
        'subset__offset',
        'subset__order_by',
        'subset__params',
        'subset__where_expr',
        'subset__order_by_expr',
        'cache-buster',
    ];

    /**
     * @param  array<string, mixed>  $additionalParams
     * @return array<string,mixed>
     */
    private function buildParams(
        Request $request,
        array $additionalParams,
    ): array {
        $queryParams = [];

        foreach ($this->query_params as $param) {
            if ($request->query->has($param)) {
                $value = $request->query->get($param);
                $queryParams[$param] = $value;
            }
        }

        foreach ($additionalParams as $key => $value) {
            $queryParams[$key] = $value;
        }

        if (! config('electricsql.electric_insecure')) {
            $queryParams['secret'] = config('electricsql.electric_secret');
        }

        return $queryParams;
    }

    /**
     * @param  array<string, list<string>>  $headers
     * @return array<string, string|null>
     */
    private function sanitizeHeaders(array $headers): array
    {
        $headers = Arr::map($headers, fn (array $values) => $values[0] ?? null);
        $headers = Arr::except($headers, ['transfer-encoding']);

        return $headers;
    }

    /**
     * @param  array<string, mixed>  $additionalParams
     */
    public function toResponse(
        Request $request,
        array $additionalParams,
    ): Response {
        $params = $this->buildParams($request, $additionalParams);
        $url = config('electricsql.electric_url');
        $electricResponse = Http::get(rtrim($url, '/').'/v1/shape', $params);
        $headers = $this->sanitizeHeaders($electricResponse->headers());
        $response = new Response;
        $response->setContent($electricResponse->body());
        $response->headers->replace($headers);
        $response->setStatusCode($electricResponse->status());

        return $response;
    }
}
