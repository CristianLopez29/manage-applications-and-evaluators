<?php

namespace Src\Candidates;

use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Src\Candidates\Domain\Repositories\CandidateEvaluationRepository;
use Src\Candidates\Domain\Repositories\CandidateRepository;
use Src\Candidates\Domain\Services\AiScreeningService;
use Src\Candidates\Infrastructure\Ai\GeminiScreeningAdapter;
use Src\Candidates\Infrastructure\Ai\OpenAiScreeningAdapter;
use Src\Candidates\Infrastructure\Controllers\AnalyzeCandidateController;
use Src\Candidates\Infrastructure\Controllers\DownloadCandidateCvController;
use Src\Candidates\Infrastructure\Controllers\GetCandidateEvaluationController;
use Src\Candidates\Infrastructure\Controllers\GetCandidateSummaryController;
use Src\Candidates\Infrastructure\Controllers\ListCandidatesController;
use Src\Candidates\Infrastructure\Controllers\RegisterCandidacyController;
use Src\Candidates\Infrastructure\Persistence\EloquentCandidateEvaluationRepository;
use Src\Candidates\Infrastructure\Persistence\EloquentCandidateRepository;

class Bindings extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(
            CandidateRepository::class,
            EloquentCandidateRepository::class
        );

        $this->app->bind(
            CandidateEvaluationRepository::class,
            EloquentCandidateEvaluationRepository::class
        );

        $this->app->bind(AiScreeningService::class, function ($app) {
            $provider = env('AI_PROVIDER', 'openai');

            if ($provider === 'gemini') {
                return $app->make(GeminiScreeningAdapter::class);
            }

            return $app->make(OpenAiScreeningAdapter::class);
        });
    }

    public function boot(Router $router): void
    {
        Route::prefix('api')->middleware(['auth:sanctum', 'throttle:60,1'])->group(function () {
            Route::post('/candidates', RegisterCandidacyController::class)->middleware('role:admin,candidate');
            Route::get('/candidates', ListCandidatesController::class)->middleware('role:admin');
            Route::get('/candidates/search', ListCandidatesController::class)->middleware('role:admin');
            Route::get('/candidates/{id}/summary', GetCandidateSummaryController::class)->middleware('can.view.candidate');
            Route::get('/candidates/{id}/cv', DownloadCandidateCvController::class)->middleware('can.view.candidate');
            Route::post('/candidates/{id}/analyze', AnalyzeCandidateController::class)->middleware('role:admin,candidate');
            Route::get('/candidates/{id}/evaluation', GetCandidateEvaluationController::class)->middleware('can.view.candidate');
        });
    }
}
