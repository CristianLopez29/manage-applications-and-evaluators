<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use Src\Candidates\Infrastructure\Http\RegisterCandidacyController;
use Src\Candidates\Infrastructure\Http\GetCandidateSummaryController;
use Src\Candidates\Infrastructure\Http\ListCandidatesController;
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

Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);

    Route::post('/candidates', RegisterCandidacyController::class);
    Route::get('/candidates', ListCandidatesController::class);
    Route::get('/candidates/search', ListCandidatesController::class);
    Route::get('/candidates/{id}/summary', GetCandidateSummaryController::class);

    Route::post('/evaluators', RegisterEvaluatorController::class);
    Route::get('/evaluators/consolidated', GetConsolidatedEvaluatorsController::class);
    Route::post('/evaluators/report', RequestEvaluatorsReportController::class);
    Route::post('/evaluators/{evaluatorId}/assign-candidate', AssignCandidateController::class);
    Route::get('/evaluators/{evaluatorId}/candidates', GetEvaluatorCandidatesController::class);
    Route::put('/evaluators/{evaluatorId}/assignments/{candidateId}/start-progress', StartAssignmentProgressController::class);
    Route::put('/evaluators/{evaluatorId}/assignments/{candidateId}/complete', CompleteAssignmentController::class);
    Route::put('/evaluators/{evaluatorId}/assignments/{candidateId}/reject', RejectAssignmentController::class);
    Route::delete('/evaluators/{evaluatorId}/assignments/{candidateId}', UnassignCandidateController::class);
    Route::put('/evaluators/{newEvaluatorId}/reassign-candidate/{candidateId}', ReassignCandidateController::class);
});
