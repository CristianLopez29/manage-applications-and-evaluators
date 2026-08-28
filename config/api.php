<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| API Rate Limiting
|--------------------------------------------------------------------------
|
| Requests per minute allowed on the authenticated business API, applied by
| the named 'api' limiter in AppServiceProvider.
|
| Configurable so a load test can measure the application instead of the
| limiter. Raising it is a deliberate, temporary act: leave the production
| value at the default.
|
*/

return [
    'rate_limit_per_minute' => (int) env('API_RATE_LIMIT_PER_MINUTE', 60),
];
