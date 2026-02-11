<?php

namespace Tests\Feature\Evaluators;

use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AssignCandidateTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function should_assign_candidate_to_evaluator(): void
    {
        // Create a candidate
        $candidateResponse = $this->postJson('/api/candidates', [
            'name' => 'Juan Pérez',
            'email' => 'juan@example.com',
            'years_of_experience' => 5,
            'cv' => 'Experiencia en desarrollo backend...',
        ]);

        // Create an evaluator
        $this->postJson('/api/evaluators', [
            'name' => 'María González',
            'email' => 'maria@example.com',
            'specialty' => 'Backend',
        ]);

        $candidate = \Src\Candidates\Infrastructure\Persistence\CandidateModel::first();
        $this->assertNotNull($candidate);
        $candidateId = $candidate->id;
        
        $evaluator = \Src\Evaluators\Infrastructure\Persistence\EvaluatorModel::first();
        $this->assertNotNull($evaluator);
        $evaluatorId = $evaluator->id;

        // Assign candidate to evaluator
        $response = $this->postJson("/api/evaluators/{$evaluatorId}/assign-candidate", [
            'candidate_id' => $candidateId,
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Candidate assigned successfully',
                'data' => [
                    'candidate_id' => $candidateId,
                    'evaluator_id' => $evaluatorId
                ]
            ]);

        $this->assertDatabaseHas('candidate_assignments', [
            'candidate_id' => $candidateId,
            'evaluator_id' => $evaluatorId,
            'status' => 'pending',
        ]);
    }

    #[Test]
    public function should_reject_assignment_with_nonexistent_candidate(): void
    {
        // Create only an evaluator
        $this->postJson('/api/evaluators', [
            'name' => 'María González',
            'email' => 'maria@example.com',
            'specialty' => 'Backend',
        ]);

        $evaluator = \Src\Evaluators\Infrastructure\Persistence\EvaluatorModel::first();
        $this->assertNotNull($evaluator);
        $evaluatorId = $evaluator->id;

        $response = $this->postJson("/api/evaluators/{$evaluatorId}/assign-candidate", [
            'candidate_id' => 999, // Does not exist
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['candidate_id']);
    }

    #[Test]
    public function should_reject_assignment_with_nonexistent_evaluator(): void
    {
        // Create only a candidate
        $this->postJson('/api/candidates', [
            'name' => 'Juan Pérez',
            'email' => 'juan@example.com',
            'years_of_experience' => 5,
            'cv' => 'Mi CV',
        ]);

        $candidate = \Src\Candidates\Infrastructure\Persistence\CandidateModel::first();
        $this->assertNotNull($candidate);
        $candidateId = $candidate->id;

        $response = $this->postJson("/api/evaluators/999/assign-candidate", [
            'candidate_id' => $candidateId,
        ]);

        $response->assertStatus(404);
    }

    #[Test]
    public function should_prevent_assigning_candidate_to_multiple_evaluators(): void
    {
        // Create a candidate
        $this->postJson('/api/candidates', [
            'name' => 'Juan Pérez',
            'email' => 'juan@example.com',
            'years_of_experience' => 5,
            'cv' => 'Mi CV',
        ]);

        // Create two evaluators
        $this->postJson('/api/evaluators', [
            'name' => 'María González',
            'email' => 'maria@example.com',
            'specialty' => 'Backend',
        ]);

        $this->postJson('/api/evaluators', [
            'name' => 'Pedro Sánchez',
            'email' => 'pedro@example.com',
            'specialty' => 'Frontend',
        ]);

        $candidate = \Src\Candidates\Infrastructure\Persistence\CandidateModel::first();
        $this->assertNotNull($candidate);
        $candidateId = $candidate->id;
        $evaluator1 = \Src\Evaluators\Infrastructure\Persistence\EvaluatorModel::first();
        $this->assertNotNull($evaluator1);
        $evaluator1Id = $evaluator1->id;
        $evaluator2 = \Src\Evaluators\Infrastructure\Persistence\EvaluatorModel::skip(1)->first();
        $this->assertNotNull($evaluator2);
        $evaluator2Id = $evaluator2->id;

        // First assignment
        $response1 = $this->postJson("/api/evaluators/{$evaluator1Id}/assign-candidate", [
            'candidate_id' => $candidateId,
        ]);
        $response1->assertStatus(200);

        // Second assignment (should fail)
        $response2 = $this->postJson("/api/evaluators/{$evaluator2Id}/assign-candidate", [
            'candidate_id' => $candidateId,
        ]);
        $response2->assertStatus(409); // Conflict
    }

    #[Test]
    public function should_require_candidate_id_field(): void
    {
        // Create evaluator
        $this->postJson('/api/evaluators', [
            'name' => 'María González',
            'email' => 'maria@example.com',
            'specialty' => 'Backend',
        ]);

        $evaluator = \Src\Evaluators\Infrastructure\Persistence\EvaluatorModel::first();
        $this->assertNotNull($evaluator);
        $evaluatorId = $evaluator->id;

        $response = $this->postJson("/api/evaluators/{$evaluatorId}/assign-candidate", []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['candidate_id']);
    }
}
