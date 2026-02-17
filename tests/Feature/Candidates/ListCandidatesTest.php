<?php

namespace Tests\Feature\Candidates;

use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ListCandidatesTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function should_filter_unassigned_candidates(): void
    {
        $backendSpecialty = 'Backend';

        $this->postJson('/api/candidates', [
            'name' => 'Assigned Candidate',
            'email' => 'assigned@example.com',
            'years_of_experience' => 5,
            'cv' => 'CV A',
            'primary_specialty' => $backendSpecialty,
        ]);

        $this->postJson('/api/candidates', [
            'name' => 'Unassigned Candidate',
            'email' => 'unassigned@example.com',
            'years_of_experience' => 3,
            'cv' => 'CV B',
            'primary_specialty' => $backendSpecialty,
        ]);

        $this->postJson('/api/evaluators', [
            'name' => 'Backend Evaluator',
            'email' => 'evaluator@example.com',
            'specialty' => $backendSpecialty,
        ]);

        $candidateAssigned = \Src\Candidates\Infrastructure\Persistence\CandidateModel::where('email', 'assigned@example.com')->first();
        $this->assertNotNull($candidateAssigned);
        $candidateAssignedId = $candidateAssigned->id;

        $evaluator = \Src\Evaluators\Infrastructure\Persistence\EvaluatorModel::first();
        $this->assertNotNull($evaluator);
        $evaluatorId = $evaluator->id;

        $assignResponse = $this->postJson("/api/evaluators/{$evaluatorId}/assign-candidate", [
            'candidate_id' => $candidateAssignedId,
        ]);

        $assignResponse->assertStatus(200);

        $response = $this->getJson('/api/candidates?status=unassigned');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonFragment([
                'email' => 'unassigned@example.com',
            ]);
    }

    #[Test]
    public function should_filter_candidates_by_minimum_experience(): void
    {
        $this->postJson('/api/candidates', [
            'name' => 'Junior',
            'email' => 'junior@example.com',
            'years_of_experience' => 1,
            'cv' => 'Junior CV',
        ]);

        $this->postJson('/api/candidates', [
            'name' => 'Senior',
            'email' => 'senior@example.com',
            'years_of_experience' => 5,
            'cv' => 'Senior CV',
        ]);

        $response = $this->getJson('/api/candidates?experience_min=2');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonFragment([
                'email' => 'senior@example.com',
            ]);
    }

    #[Test]
    public function should_search_candidates_by_partial_email(): void
    {
        $this->postJson('/api/candidates', [
            'name' => 'Juan',
            'email' => 'juan@example.com',
            'years_of_experience' => 3,
            'cv' => 'CV Juan',
        ]);

        $this->postJson('/api/candidates', [
            'name' => 'Ana',
            'email' => 'ana@example.com',
            'years_of_experience' => 4,
            'cv' => 'CV Ana',
        ]);

        $response = $this->getJson('/api/candidates/search?email=juan@');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonFragment([
                'email' => 'juan@example.com',
            ]);
    }

    #[Test]
    public function should_filter_candidates_by_primary_specialty(): void
    {
        $this->postJson('/api/candidates', [
            'name' => 'Backend Dev',
            'email' => 'backend@example.com',
            'years_of_experience' => 4,
            'cv' => 'Backend CV',
            'primary_specialty' => 'Backend',
        ]);

        $this->postJson('/api/candidates', [
            'name' => 'Frontend Dev',
            'email' => 'frontend@example.com',
            'years_of_experience' => 4,
            'cv' => 'Frontend CV',
            'primary_specialty' => 'Frontend',
        ]);

        $response = $this->getJson('/api/candidates?specialty=Backend');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonFragment([
                'email' => 'backend@example.com',
            ]);
    }
}
