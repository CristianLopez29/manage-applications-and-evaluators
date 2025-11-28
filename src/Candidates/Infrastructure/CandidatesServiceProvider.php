<?php

namespace Src\Candidates\Infrastructure;

use Illuminate\Support\ServiceProvider;
use Src\Candidates\Domain\Repositories\CandidateRepository;
use Src\Candidates\Infrastructure\Persistence\EloquentCandidateRepository;

class CandidatesServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(
            CandidateRepository::class,
            EloquentCandidateRepository::class
        );
    }
}