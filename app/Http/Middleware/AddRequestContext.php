<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

/**
 * Binds a correlation id and the authenticated user to the logging context so
 * every log line produced during the request can be traced back to it. The id
 * is echoed back on the response (and honoured if the caller already sent one),
 * which lets clients and upstream proxies stitch logs together end to end.
 */
class AddRequestContext
{
    private const REQUEST_ID_HEADER = 'X-Request-Id';

    public function handle(Request $request, Closure $next): Response
    {
        $incoming = $request->headers->get(self::REQUEST_ID_HEADER);
        $requestId = is_string($incoming) && $incoming !== ''
            ? $incoming
            : (string) Str::uuid();

        Log::withContext([
            'request_id' => $requestId,
            'user_id' => $request->user()?->getAuthIdentifier(),
            'method' => $request->getMethod(),
            'path' => $request->path(),
        ]);

        /** @var Response $response */
        $response = $next($request);
        $response->headers->set(self::REQUEST_ID_HEADER, $requestId);

        return $response;
    }
}
