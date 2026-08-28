<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Gates the liveness and readiness probes behind a shared secret outside of
 * local and testing, so the dependency report (which reveals whether the
 * database and cache are reachable) is not public.
 *
 * Fails closed: with no token configured the probes stay locked rather than
 * opening up, which is the safer default if the deploy forgets the variable.
 */
class EnsureHealthCheckToken
{
    private const TOKEN_HEADER = 'X-Health-Check-Token';

    public function handle(Request $request, Closure $next): Response
    {
        if (app()->environment('local', 'testing')) {
            return $next($request);
        }

        $configured = config('healthcheck.token');
        $provided = $request->header(self::TOKEN_HEADER);

        if (!is_string($configured) || $configured === '') {
            abort(Response::HTTP_FORBIDDEN);
        }

        if (!is_string($provided) || !hash_equals($configured, $provided)) {
            abort(Response::HTTP_FORBIDDEN);
        }

        return $next($request);
    }
}
