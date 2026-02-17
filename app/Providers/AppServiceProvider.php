<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

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
    }
}
