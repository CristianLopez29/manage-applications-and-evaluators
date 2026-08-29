<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use Src\Candidates\Infrastructure\Persistence\CandidateModel;
use Src\Evaluators\Application\Ports\EvaluatorCachePort;
use Src\Evaluators\Infrastructure\Persistence\CandidateAssignmentModel;
use Src\Evaluators\Infrastructure\Persistence\EvaluatorModel;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
 * make load-test raises the rate limit and restores it when the k6 run ends, but it never
 * cleaned up the evaluators/candidates the scenario registers. Every one of them is named by
 * load-tests/candidacy-flow.js with a load.evaluator./load.candidate. email prefix, so this
 * targets exactly those rows and nothing a real seed or a manual test could have created.
 */
Artisan::command('load-test:cleanup', function () {
    $evaluatorIds = EvaluatorModel::where('email', 'like', 'load.evaluator.%')->pluck('id');
    $candidateIds = CandidateModel::where('email', 'like', 'load.candidate.%')->pluck('id');

    $assignments = CandidateAssignmentModel::whereIn('evaluator_id', $evaluatorIds)
        ->orWhereIn('candidate_id', $candidateIds)
        ->delete();
    $candidates = CandidateModel::whereIn('id', $candidateIds)->delete();
    $evaluators = EvaluatorModel::whereIn('id', $evaluatorIds)->delete();

    app(EvaluatorCachePort::class)->flush();

    $this->info("Removed {$assignments} assignments, {$candidates} candidates, {$evaluators} evaluators seeded by load-tests/candidacy-flow.js.");
})->purpose('Delete the evaluators, candidates and assignments seeded by make load-test');

// Schedule: process overdue candidate assignments every 15 minutes
Schedule::job(\Src\Evaluators\Infrastructure\Jobs\ProcessOverdueAssignmentsJob::class)
    ->everyFifteenMinutes();
