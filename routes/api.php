<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Src\Candidates\Infrastructure\Http\RegisterCandidacyController;
use Src\Candidates\Infrastructure\Http\GetCandidateSummaryController;
use Src\Evaluators\Infrastructure\Http\RegisterEvaluatorController;
use Src\Evaluators\Infrastructure\Http\AssignCandidateController;
use Src\Evaluators\Infrastructure\Http\GetEvaluatorCandidatesController;
use Src\Evaluators\Infrastructure\Http\GetConsolidatedEvaluatorsController;
use Src\Evaluators\Infrastructure\Http\RequestEvaluatorsReportController;

// Candidate routes
Route::post('/candidates', RegisterCandidacyController::class);
Route::get('/candidates/{id}/summary', GetCandidateSummaryController::class);

// Evaluator routes
Route::post('/evaluators', RegisterEvaluatorController::class);
Route::get('/evaluators/consolidated', GetConsolidatedEvaluatorsController::class);
Route::post('/evaluators/report', RequestEvaluatorsReportController::class);
Route::post('/evaluators/{evaluatorId}/assign-candidate', AssignCandidateController::class);
Route::get('/evaluators/{evaluatorId}/candidates', GetEvaluatorCandidatesController::class);