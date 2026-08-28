<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeaders
{
    /**
     * Two years, the minimum the browser preload lists accept.
     */
    private const HSTS_MAX_AGE = 63072000;

    public function handle(Request $request, Closure $next): Response
    {
        /** @var Response $response */
        $response = $next($request);

        foreach ($this->headersFor($request) as $name => $value) {
            if (!$response->headers->has($name)) {
                $response->headers->set($name, $value);
            }
        }

        // Advertising the runtime only helps someone matching the host against
        // a CVE list; PHP adds this one itself when expose_php is on.
        $response->headers->remove('X-Powered-By');

        return $response;
    }

    /**
     * @return array<string, string>
     */
    private function headersFor(Request $request): array
    {
        $headers = [
            'X-Content-Type-Options' => 'nosniff',
            'X-Frame-Options' => 'DENY',
            'Referrer-Policy' => 'strict-origin-when-cross-origin',
            'Permissions-Policy' => 'geolocation=(), microphone=(), camera=()',
        ];

        // Sending HSTS over plain HTTP is ignored by browsers, and pinning it
        // during local development would make http://localhost unreachable in
        // that browser profile for two years.
        if ($request->isSecure()) {
            $headers['Strict-Transport-Security'] = 'max-age=' . self::HSTS_MAX_AGE . '; includeSubDomains';
        }

        return $headers;
    }
}
