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

    Route::post('/candidates', RegisterCandidacyController::class)->middleware('role:admin');
    Route::get('/candidates', ListCandidatesController::class)->middleware('role:admin');
    Route::get('/candidates/search', ListCandidatesController::class)->middleware('role:admin');
    Route::get('/candidates/{id}/summary', GetCandidateSummaryController::class)->middleware('can.view.candidate');

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
