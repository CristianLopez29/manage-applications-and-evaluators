<?php

namespace Src\Evaluators\Infrastructure;

use Illuminate\Support\ServiceProvider;
use Src\Evaluators\Domain\Repositories\AssignmentRepository;
use Src\Evaluators\Domain\Repositories\EvaluatorRepository;
use Src\Evaluators\Infrastructure\Persistence\EloquentAssignmentRepository;
use Src\Evaluators\Infrastructure\Persistence\EloquentEvaluatorRepository;

class EvaluatorsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Bind repository interfaces to implementations
        $this->app->bind(
            EvaluatorRepository::class,
            EloquentEvaluatorRepository::class
        );

        $this->app->bind(
            AssignmentRepository::class,
            EloquentAssignmentRepository::class
        );
    }

    public function boot(): void
    {
        // Load migrations
        $this->loadMigrationsFrom(__DIR__ . '/../../database/migrations');
    }
}
