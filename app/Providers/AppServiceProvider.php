<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
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
        // Telescope is a require-dev package: registering its provider
        // unconditionally makes `composer install --no-dev` fatal on boot,
        // because App\Providers\TelescopeServiceProvider extends a vendor
        // class that is not installed in production.
        if ($this->app->environment('local') && class_exists(\Laravel\Telescope\TelescopeServiceProvider::class)) {
            $this->app->register(\Laravel\Telescope\TelescopeServiceProvider::class);
            $this->app->register(\App\Providers\TelescopeServiceProvider::class);
        }

        $this->app->bind(AuditLogger::class, EloquentAuditLogger::class);

        $this->app->bind(DomainEventPublisher::class, LaravelDomainEventPublisher::class);

        $this->app->bind(TransactionManager::class, LaravelTransactionManager::class);
    }

    public function boot(): void
    {
        // Behind a TLS-terminating proxy the app only ever sees plain HTTP;
        // without this, generated URLs (Swagger server URL, mailed report
        // links) come out as http:// and get blocked as mixed content.
        if ($this->app->environment('production')) {
            URL::forceScheme('https');
        }

        // Keyed by token owner when authenticated so one noisy client cannot
        // spend a shared IP's quota (several users behind one NAT, or every
        // request arriving from the reverse proxy).
        RateLimiter::for('api', function (Request $request) {
            $perMinute = config('api.rate_limit_per_minute', 60);

            return Limit::perMinute(is_numeric($perMinute) ? (int) $perMinute : 60)
                ->by($request->user()?->getAuthIdentifier() ?? $request->ip());
        });

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
