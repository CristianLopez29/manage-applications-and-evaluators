<?php

namespace Src\Candidates\Infrastructure;

use Illuminate\Support\ServiceProvider;
use Src\Candidates\Domain\Repositories\CandidateRepository;
use Src\Candidates\Infrastructure\Persistence\EloquentCandidateRepository;
use Src\Candidates\Domain\Repositories\CandidateEvaluationRepository;
use Src\Candidates\Infrastructure\Persistence\EloquentCandidateEvaluationRepository;
use Src\Candidates\Domain\Services\AiScreeningService;
use Src\Candidates\Infrastructure\Ai\OpenAiScreeningAdapter;
use Src\Candidates\Infrastructure\Ai\GeminiScreeningAdapter;

class CandidatesServiceProvider extends ServiceProvider
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
}
