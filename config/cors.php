<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Cross-Origin Resource Sharing (CORS)
|--------------------------------------------------------------------------
|
| Published to replace the framework default of allowed_origins => ['*'],
| which let any site on the internet call this API from a visitor's browser.
|
| Set CORS_ALLOWED_ORIGINS to a comma-separated list of the front-ends that
| should be able to call the API. The default is empty: with no browser
| client to serve, server-to-server callers and curl ignore CORS entirely,
| so nothing is lost by starting closed.
|
*/

$origins = array_values(array_filter(array_map(
    'trim',
    explode(',', (string) env('CORS_ALLOWED_ORIGINS', ''))
)));

return [
    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    'allowed_methods' => ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'],

    'allowed_origins' => $origins,

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['Accept', 'Authorization', 'Content-Type', 'X-Request-Id', 'X-Requested-With'],

    'exposed_headers' => ['X-Request-Id'],

    'max_age' => 3600,

    // Auth is Bearer-token based, so the browser never needs to send cookies.
    'supports_credentials' => false,
];
