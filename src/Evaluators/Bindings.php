<?php

namespace Src\Evaluators;

use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Src\Evaluators\Domain\Repositories\AssignmentRepository;
use Src\Evaluators\Domain\Repositories\EvaluatorRepository;
use Src\Evaluators\Infrastructure\Controllers\AssignCandidateController;
use Src\Evaluators\Infrastructure\Controllers\CompleteAssignmentController;
use Src\Evaluators\Infrastructure\Controllers\GetConsolidatedEvaluatorsController;
use Src\Evaluators\Infrastructure\Controllers\GetEvaluatorCandidatesController;
use Src\Evaluators\Infrastructure\Controllers\ReassignCandidateController;
use Src\Evaluators\Infrastructure\Controllers\RegisterEvaluatorController;
use Src\Evaluators\Infrastructure\Controllers\RejectAssignmentController;
use Src\Evaluators\Infrastructure\Controllers\RequestEvaluatorsReportController;
use Src\Evaluators\Infrastructure\Controllers\StartAssignmentProgressController;
use Src\Evaluators\Infrastructure\Controllers\UnassignCandidateController;
use Src\Evaluators\Infrastructure\Persistence\EloquentAssignmentRepository;
use Src\Evaluators\Infrastructure\Persistence\EloquentEvaluatorRepository;

class Bindings extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(
            EvaluatorRepository::class,
            EloquentEvaluatorRepository::class
        );

        $this->app->bind(
            AssignmentRepository::class,
            EloquentAssignmentRepository::class
        );
    }

    public function boot(Router $router): void
    {
        Route::prefix('api')->middleware(['auth:sanctum', 'throttle:60,1'])->group(function () {
            Route::post('/evaluators', RegisterEvaluatorController::class)->middleware('role:admin');
            Route::get('/evaluators/consolidated', GetConsolidatedEvaluatorsController::class)->middleware('role:admin');
            Route::post('/evaluators/report', RequestEvaluatorsReportController::class)->middleware('role:admin');
            Route::post('/evaluators/{evaluatorId}/assign-candidate', AssignCandidateController::class)->middleware('role:admin');
            Route::get('/evaluators/{evaluatorId}/candidates', GetEvaluatorCandidatesController::class)->middleware('can.view.evaluator');
            Route::put('/evaluators/{evaluatorId}/assignments/{candidateId}/start-progress', StartAssignmentProgressController::class)->middleware('role:admin');
            Route::put('/evaluators/{evaluatorId}/assignments/{candidateId}/complete', CompleteAssignmentController::class)->middleware('role:admin');
            Route::put('/evaluators/{evaluatorId}/assignments/{candidateId}/reject', RejectAssignmentController::class)->middleware('role:admin');
            Route::delete('/evaluators/{evaluatorId}/assignments/{candidateId}', UnassignCandidateController::class)->middleware('role:admin');
            Route::put('/evaluators/{newEvaluatorId}/reassign-candidate/{candidateId}', ReassignCandidateController::class)->middleware('role:admin');
        });
    }
}
