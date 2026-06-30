<?php

use App\Http\Middleware\SecurityHeaders;
use App\Http\Middleware\EnsureCandidateAccess;
use App\Http\Middleware\EnsureEvaluatorAccess;
use App\Http\Middleware\AddRequestContext;
use Illuminate\Http\Request;
use App\Http\Middleware\EnsureRole;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->append(SecurityHeaders::class);

        $middleware->alias([
            'role' => EnsureRole::class,
            'can.view.candidate' => EnsureCandidateAccess::class,
            'can.view.evaluator' => EnsureEvaluatorAccess::class,
            'request.context' => AddRequestContext::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
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
