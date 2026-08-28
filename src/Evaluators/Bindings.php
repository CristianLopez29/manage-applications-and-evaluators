<?php

namespace Src\Evaluators;

use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Src\Evaluators\Application\Ports\EvaluatorCachePort;
use Src\Evaluators\Domain\Events\AssignmentStatusChanged;
use Src\Evaluators\Domain\Events\CandidateAssigned;
use Src\Evaluators\Domain\Repositories\AssignmentHistoryRepository;
use Src\Evaluators\Domain\Repositories\AssignmentRepository;
use Src\Evaluators\Domain\Repositories\EvaluatorRepository;
use Src\Evaluators\Infrastructure\Cache\LaravelEvaluatorCache;
use Src\Evaluators\Infrastructure\Controllers\AssignCandidateController;
use Src\Evaluators\Infrastructure\Controllers\GetCandidateAssignmentHistoryController;
use Src\Evaluators\Infrastructure\Controllers\CompleteAssignmentController;
use Src\Evaluators\Infrastructure\Controllers\GetConsolidatedEvaluatorsController;
use Src\Evaluators\Infrastructure\Controllers\GetEvaluatorCandidatesController;
use Src\Evaluators\Infrastructure\Controllers\ReassignCandidateController;
use Src\Evaluators\Infrastructure\Controllers\RegisterEvaluatorController;
use Src\Evaluators\Infrastructure\Controllers\RejectAssignmentController;
use Src\Evaluators\Infrastructure\Controllers\RequestEvaluatorsReportController;
use Src\Evaluators\Infrastructure\Controllers\StartAssignmentProgressController;
use Src\Evaluators\Infrastructure\Controllers\UnassignCandidateController;
use Src\Evaluators\Infrastructure\Listeners\InvalidateEvaluatorCache;
use Src\Evaluators\Infrastructure\Listeners\LogCandidateAssignment;
use Src\Evaluators\Infrastructure\Listeners\RecordAssignmentHistory;
use Src\Evaluators\Infrastructure\Listeners\SendAssignmentNotifications;
use Src\Evaluators\Infrastructure\Listeners\SendAssignmentStatusChangeNotifications;
use Src\Evaluators\Infrastructure\Persistence\EloquentAssignmentHistoryRepository;
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

        $this->app->bind(
            EvaluatorCachePort::class,
            LaravelEvaluatorCache::class
        );

        $this->app->bind(
            AssignmentHistoryRepository::class,
            EloquentAssignmentHistoryRepository::class
        );
    }

    public function boot(): void
    {
        Event::listen(CandidateAssigned::class, LogCandidateAssignment::class);
        Event::listen(CandidateAssigned::class, SendAssignmentNotifications::class);
        Event::listen(CandidateAssigned::class, [RecordAssignmentHistory::class, 'handleAssigned']);
        Event::listen(CandidateAssigned::class, InvalidateEvaluatorCache::class);

        Event::listen(AssignmentStatusChanged::class, SendAssignmentStatusChangeNotifications::class);
        Event::listen(AssignmentStatusChanged::class, [RecordAssignmentHistory::class, 'handleStatusChanged']);
        Event::listen(AssignmentStatusChanged::class, InvalidateEvaluatorCache::class);

        Route::prefix('api/v1')->middleware(['auth:sanctum', 'throttle:60,1', 'request.context'])->group(function () {
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
            Route::get('/candidates/{candidateId}/assignment-history', GetCandidateAssignmentHistoryController::class)->middleware('role:admin');
        });
    }
}

