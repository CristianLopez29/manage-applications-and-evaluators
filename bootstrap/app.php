<?php

use App\Http\Middleware\SecurityHeaders;
use App\Http\Middleware\EnsureCandidateAccess;
use App\Http\Middleware\EnsureEvaluatorAccess;
use App\Http\Middleware\AddRequestContext;
use App\Http\Middleware\EnsureHealthCheckToken;
use Illuminate\Http\Request;
use App\Http\Middleware\EnsureRole;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Sentry\Laravel\Integration;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->append(SecurityHeaders::class);

        // Behind the VPS reverse proxy every request otherwise arrives from the
        // proxy's own address: throttling would collapse into a single shared
        // bucket and isSecure() would report false on HTTPS traffic. Only
        // loopback and private ranges are trusted, so a directly exposed app
        // still ignores forged X-Forwarded-* headers from the internet.
        $middleware->trustProxies(
            at: [
                '127.0.0.1',
                '::1',
                '10.0.0.0/8',
                '172.16.0.0/12',
                '192.168.0.0/16',
            ],
            headers: Request::HEADER_X_FORWARDED_FOR
                | Request::HEADER_X_FORWARDED_HOST
                | Request::HEADER_X_FORWARDED_PORT
                | Request::HEADER_X_FORWARDED_PROTO,
        );

        $middleware->alias([
            'role' => EnsureRole::class,
            'can.view.candidate' => EnsureCandidateAccess::class,
            'can.view.evaluator' => EnsureEvaluatorAccess::class,
            'request.context' => AddRequestContext::class,
            'health.token' => EnsureHealthCheckToken::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        // sentry-laravel deliberately removes its own error listeners and
        // expects the application to opt in here. Without this call the SDK
        // boots, reads the DSN and reports nothing at all.
        Integration::handles($exceptions);

        // Domain exceptions are business outcomes mapped to 4xx below, not
        // incidents: reporting them would bury real 5xx under validation noise.
        $exceptions->dontReport([
            \DomainException::class,
        ]);

        // Map all DomainExceptions to HTTP 422 Unprocessable Entity
        $exceptions->render(function (\DomainException $e, Request $request) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => $e->getMessage(),
                    'type'    => class_basename($e),
                ], 422);
            }
        });

        // Map EvaluatorNotFoundException to HTTP 404
        $exceptions->render(function (\Src\Evaluators\Domain\Exceptions\EvaluatorNotFoundException $e, Request $request) {
            if ($request->expectsJson()) {
                return response()->json(['message' => $e->getMessage()], 404);
            }
        });
    })->create();
