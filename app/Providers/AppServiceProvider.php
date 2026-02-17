<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Http\Request;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Audit Logger Binding
        $this->app->bind(
            \Src\Shared\Domain\Audit\AuditLogger::class,
            \Src\Shared\Infrastructure\Audit\EloquentAuditLogger::class
        );
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        \Illuminate\Support\Facades\Event::listen(
            \Src\Candidates\Domain\Events\CandidateRegistered::class,
            \Src\Candidates\Infrastructure\Listeners\LogCandidateAction::class
        );

        \Illuminate\Support\Facades\Event::listen(
            \Src\Evaluators\Domain\Events\CandidateAssigned::class,
            \Src\Evaluators\Infrastructure\Listeners\LogCandidateAssignment::class
        );

        \Illuminate\Support\Facades\Event::listen(
            \Src\Evaluators\Domain\Events\CandidateAssigned::class,
            \Src\Evaluators\Infrastructure\Listeners\SendAssignmentNotifications::class
        );

        \Illuminate\Support\Facades\Event::listen(
            \Src\Evaluators\Domain\Events\AssignmentStatusChanged::class,
            \Src\Evaluators\Infrastructure\Listeners\SendAssignmentStatusChangeNotifications::class
        );

        RateLimiter::for('login', function (Request $request) {
            $email = (string) $request->input('email');
            return [
                Limit::perMinute(5)->by($email.$request->ip()),
                Limit::perMinute(30)->by($request->ip()),
            ];
        });
    }
}
