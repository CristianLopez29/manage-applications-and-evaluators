<?php

namespace Tests\Evaluators\Acceptance;

use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class UnassignCandidateTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function should_unassign_candidate_from_evaluator(): void
    {
        $this->postJson('/api/candidates', [
            'name' => 'Unassign Me',
            'email' => 'unassign@example.com',
            'years_of_experience' => 4,
            'cv' => 'CV',
            'primary_specialty' => 'Backend',
        ])->assertStatus(201);

        $this->postJson('/api/evaluators', [
            'name' => 'Eval U',
            'email' => 'eval.u@example.com',
            'specialty' => 'Backend',
        ])->assertStatus(201);

        $candidate = \Src\Candidates\Infrastructure\Persistence\CandidateModel::first();
        $evaluator = \Src\Evaluators\Infrastructure\Persistence\EvaluatorModel::first();
        $this->assertNotNull($candidate);
        $this->assertNotNull($evaluator);

        $this->postJson("/api/evaluators/{$evaluator->id}/assign-candidate", [
            'candidate_id' => $candidate->id,
        ])->assertStatus(200);

        $this->deleteJson("/api/evaluators/{$evaluator->id}/assignments/{$candidate->id}")
            ->assertStatus(200);

        $this->assertDatabaseMissing('candidate_assignments', [
            'candidate_id' => $candidate->id,
            'evaluator_id' => $evaluator->id,
        ]);
    }
}
