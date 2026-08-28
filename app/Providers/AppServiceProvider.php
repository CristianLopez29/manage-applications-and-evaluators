<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Src\Shared\Application\Ports\TransactionManager;
use Src\Shared\Domain\Audit\AuditLogger;
use Src\Shared\Domain\DomainEventPublisher;
use Src\Shared\Infrastructure\Audit\EloquentAuditLogger;
use Src\Shared\Infrastructure\LaravelDomainEventPublisher;
use Src\Shared\Infrastructure\LaravelTransactionManager;

/**
 * Shared kernel wiring only.
 *
 * Domain event listeners belong to the module that owns the event and are
 * registered in that module's Bindings::boot(); splitting them across two
 * providers is how a listener ends up registered twice or debugged in the
 * wrong file.
 */
class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(AuditLogger::class, EloquentAuditLogger::class);

        $this->app->bind(DomainEventPublisher::class, LaravelDomainEventPublisher::class);

        $this->app->bind(TransactionManager::class, LaravelTransactionManager::class);
    }

    public function boot(): void
    {
        RateLimiter::for('login', function (Request $request) {
            $input = $request->input('email');
            $email = is_string($input) ? $input : '';

            return [
                Limit::perMinute(5)->by($email.$request->ip()),
                Limit::perMinute(30)->by($request->ip()),
            ];
        });
    }
}
