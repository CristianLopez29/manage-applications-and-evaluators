<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Health Probe Token
|--------------------------------------------------------------------------
|
| Shared secret required by /api/health and /api/readiness outside of local
| and testing. Read through config() so the value survives config caching;
| an env() call here would resolve to null in production and lock the probes
| behind a permanent 403.
|
*/

return [
    'token' => env('HEALTHCHECK_TOKEN'),
];
