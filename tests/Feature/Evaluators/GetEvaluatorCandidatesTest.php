<?php

namespace Tests\Feature\Evaluators;

use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class GetEvaluatorCandidatesTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function should_return_all_assigned_candidates(): void
    {
        // Create evaluator
        $this->postJson('/api/evaluators', [
            'name' => 'María González',
            'email' => 'maria@example.com',
            'specialty' => 'Backend',
        ]);

        // Create candidates
        $this->postJson('/api/candidates', [
            'name' => 'Juan Pérez',
            'email' => 'juan@example.com',
            'years_of_experience' => 5,
            'cv' => 'CV de Juan',
        ]);

        $this->postJson('/api/candidates', [
            'name' => 'Ana García',
            'email' => 'ana@example.com',
            'years_of_experience' => 3,
            'cv' => 'CV de Ana',
        ]);

        $evaluatorId = \Src\Evaluators\Infrastructure\Persistence\EvaluatorModel::first()->id;
        $candidate1Id = \Src\Candidates\Infrastructure\Persistence\CandidateModel::where('email', 'juan@example.com')->first()->id;
        $candidate2Id = \Src\Candidates\Infrastructure\Persistence\CandidateModel::where('email', 'ana@example.com')->first()->id;

        // Assign both candidates
        $this->postJson("/api/evaluators/{$evaluatorId}/assign-candidate", [
            'candidate_id' => $candidate1Id,
        ]);

        $this->postJson("/api/evaluators/{$evaluatorId}/assign-candidate", [
            'candidate_id' => $candidate2Id,
        ]);

        // Get evaluator candidates
        $response = $this->getJson("/api/evaluators/{$evaluatorId}/candidates");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'name', 'email', 'years_of_experience', 'assigned_at', 'status']
                ],
                'meta' => ['total', 'evaluator_id']
            ])
            ->assertJson([
                'meta' => [
                    'total' => 2,
                    'evaluator_id' => $evaluatorId
                ]
            ]);

        // Verify that both candidates are in the response
        $data = $response->json('data');
        $emails = array_column($data, 'email');
        $this->assertContains('juan@example.com', $emails);
        $this->assertContains('ana@example.com', $emails);
    }

    #[Test]
    public function should_return_empty_for_evaluator_without_candidates(): void
    {
        // Create evaluator without candidates
        $this->postJson('/api/evaluators', [
            'name' => 'María González',
            'email' => 'maria@example.com',
            'specialty' => 'Backend',
        ]);

        $evaluatorId = \Src\Evaluators\Infrastructure\Persistence\EvaluatorModel::first()->id;

        $response = $this->getJson("/api/evaluators/{$evaluatorId}/candidates");

        $response->assertStatus(200)
            ->assertJson([
                'data' => [],
                'meta' => [
                    'total' => 0,
                    'evaluator_id' => $evaluatorId
                ]
            ]);
    }

    #[Test]
    public function should_return_404_for_nonexistent_evaluator(): void
    {
        $response = $this->getJson("/api/evaluators/999/candidates");

        $response->assertStatus(404);
    }

    #[Test]
    public function should_include_assignment_status_in_response(): void
    {
        // Create evaluator and candidate
        $this->postJson('/api/evaluators', [
            'name' => 'María González',
            'email' => 'maria@example.com',
            'specialty' => 'Backend',
        ]);

        $this->postJson('/api/candidates', [
            'name' => 'Juan Pérez',
            'email' => 'juan@example.com',
            'years_of_experience' => 5,
            'cv' => 'CV de Juan',
        ]);

        $evaluatorId = \Src\Evaluators\Infrastructure\Persistence\EvaluatorModel::first()->id;
        $candidateId = \Src\Candidates\Infrastructure\Persistence\CandidateModel::first()->id;

        // Assign
        $this->postJson("/api/evaluators/{$evaluatorId}/assign-candidate", [
            'candidate_id' => $candidateId,
        ]);

        // Get candidates
        $response = $this->getJson("/api/evaluators/{$evaluatorId}/candidates");

        $response->assertStatus(200);

        $firstCandidate = $response->json('data.0');
        $this->assertEquals('pending', $firstCandidate['status']);
        $this->assertArrayHasKey('assigned_at', $firstCandidate);
    }

    #[Test]
    public function should_only_return_candidates_for_specific_evaluator(): void
    {
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

        // Create two candidates
        $this->postJson('/api/candidates', [
            'name' => 'Juan Pérez',
            'email' => 'juan@example.com',
            'years_of_experience' => 5,
            'cv' => 'CV de Juan',
        ]);

        $this->postJson('/api/candidates', [
            'name' => 'Ana García',
            'email' => 'ana@example.com',
            'years_of_experience' => 3,
            'cv' => 'CV de Ana',
        ]);

        $evaluator1Id = \Src\Evaluators\Infrastructure\Persistence\EvaluatorModel::first()->id;
        $evaluator2Id = \Src\Evaluators\Infrastructure\Persistence\EvaluatorModel::skip(1)->first()->id;
        $candidate1Id = \Src\Candidates\Infrastructure\Persistence\CandidateModel::where('email', 'juan@example.com')->first()->id;
        $candidate2Id = \Src\Candidates\Infrastructure\Persistence\CandidateModel::where('email', 'ana@example.com')->first()->id;

        // Assign Juan to María
        $this->postJson("/api/evaluators/{$evaluator1Id}/assign-candidate", [
            'candidate_id' => $candidate1Id,
        ]);

        // Assign Ana to Pedro
        $this->postJson("/api/evaluators/{$evaluator2Id}/assign-candidate", [
            'candidate_id' => $candidate2Id,
        ]);

        // Verify that María only sees Juan
        $response1 = $this->getJson("/api/evaluators/{$evaluator1Id}/candidates");
        $response1->assertStatus(200);
        $data1 = $response1->json('data');
        $this->assertCount(1, $data1);
        $this->assertEquals('juan@example.com', $data1[0]['email']);

        // Verify that Pedro only sees Ana
        $response2 = $this->getJson("/api/evaluators/{$evaluator2Id}/candidates");
        $response2->assertStatus(200);
        $data2 = $response2->json('data');
        $this->assertCount(1, $data2);
        $this->assertEquals('ana@example.com', $data2[0]['email']);
    }
}
