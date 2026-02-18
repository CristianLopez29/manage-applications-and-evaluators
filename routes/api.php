<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ReportsController;
use Src\Candidates\Infrastructure\Http\RegisterCandidacyController;
use Src\Candidates\Infrastructure\Http\GetCandidateSummaryController;
use Src\Candidates\Infrastructure\Http\ListCandidatesController;
use Src\Candidates\Infrastructure\Http\DownloadCandidateCvController;
use Src\Evaluators\Infrastructure\Http\RegisterEvaluatorController;
use Src\Evaluators\Infrastructure\Http\AssignCandidateController;
use Src\Evaluators\Infrastructure\Http\StartAssignmentProgressController;
use Src\Evaluators\Infrastructure\Http\CompleteAssignmentController;
use Src\Evaluators\Infrastructure\Http\RejectAssignmentController;
use Src\Evaluators\Infrastructure\Http\UnassignCandidateController;
use Src\Evaluators\Infrastructure\Http\ReassignCandidateController;
use Src\Evaluators\Infrastructure\Http\GetEvaluatorCandidatesController;
use Src\Evaluators\Infrastructure\Http\GetConsolidatedEvaluatorsController;
use Src\Evaluators\Infrastructure\Http\RequestEvaluatorsReportController;

Route::get('/health', function () {
    if (app()->environment('production')) {
        $token = request()->header('X-Health-Check-Token');
        if (!is_string($token) || $token !== env('HEALTHCHECK_TOKEN')) {
            abort(403);
        }
    }
    return response()->json([
        'status' => 'ok',
        'time' => now()->toISOString(),
    ]);
});

Route::get('/readiness', function () {
    if (app()->environment('production')) {
        $token = request()->header('X-Health-Check-Token');
        if (!is_string($token) || $token !== env('HEALTHCHECK_TOKEN')) {
            abort(403);
        }
    }
    $status = 'ok';
    $checks = [];

    try {
        DB::connection()->getPdo();
        $checks['database'] = 'up';
    } catch (\Throwable $e) {
        $checks['database'] = 'down';
        $status = 'degraded';
    }

    try {
        Cache::store()->get('health_check');
        $checks['cache'] = 'up';
    } catch (\Throwable $e) {
        $checks['cache'] = 'down';
        $status = 'degraded';
    }

    return response()->json([
        'status' => $status,
        'checks' => $checks,
        'time' => now()->toISOString(),
    ]);
});

Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:login');

Route::middleware(['auth:sanctum', 'throttle:60,1'])->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::post('/refresh-token', [AuthController::class, 'refresh']);
    Route::post('/users/{id}/tokens/revoke-all', [AuthController::class, 'revokeAllTokens'])->middleware('role:admin');
    Route::get('/reports/download', [ReportsController::class, 'download'])->middleware('role:admin');

    Route::post('/candidates', RegisterCandidacyController::class)->middleware('role:admin,candidate');
    Route::get('/candidates', ListCandidatesController::class)->middleware('role:admin');
    Route::get('/candidates/search', ListCandidatesController::class)->middleware('role:admin');
    Route::get('/candidates/{id}/summary', GetCandidateSummaryController::class)->middleware('can.view.candidate');
    Route::get('/candidates/{id}/cv', DownloadCandidateCvController::class)->middleware('can.view.candidate');

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
