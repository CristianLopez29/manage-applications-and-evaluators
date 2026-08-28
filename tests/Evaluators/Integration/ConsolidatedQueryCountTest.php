<?php

namespace Tests\Evaluators\Integration;

use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ConsolidatedQueryCountTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function consolidated_list_does_not_produce_n_plus_1_queries(): void
    {
        $this->actingAsAdmin();

        // Create 5 evaluators with 2 candidates each
        for ($i = 1; $i <= 5; $i++) {
            $this->postJson('/api/v1/evaluators', [
                'name'      => "Eval {$i}",
                'email'     => "eval{$i}@example.com",
                'specialty' => 'Backend',
            ]);
        }

        // Create 10 candidates
        for ($j = 1; $j <= 10; $j++) {
            $this->postJson('/api/v1/candidates', [
                'name'                => "Candidate {$j}",
                'email'               => "candidate{$j}@example.com",
                'years_of_experience' => 3,
                'cv'                  => "CV del candidato {$j}",
            ]);
        }

        // Assign 2 candidates per evaluator
        $evaluators = \Src\Evaluators\Infrastructure\Persistence\EvaluatorModel::all();
        $candidates = \Src\Candidates\Infrastructure\Persistence\CandidateModel::all();
        $candidateIndex = 0;

        foreach ($evaluators as $evaluator) {
            for ($k = 0; $k < 2; $k++) {
                if (isset($candidates[$candidateIndex])) {
                    $this->postJson("/api/v1/evaluators/{$evaluator->id}/assign-candidate", [
                        'candidate_id' => $candidates[$candidateIndex]->id,
                    ]);
                    $candidateIndex++;
                }
            }
        }

        // Measure query count for the consolidated page
        \DB::enableQueryLog();
        $this->getJson('/api/v1/evaluators/consolidated?per_page=5')->assertStatus(200);
        $queryCount = count(\DB::getQueryLog());
        \DB::disableQueryLog();

        // With the N+1 fix: maximum 3 queries
        // (paginator COUNT query + main data query + candidates eager-load query)
        $this->assertLessThanOrEqual(
            3,
            $queryCount,
            "Expected ≤3 queries, but got {$queryCount}. Possible N+1 regression."
        );
    }
}
