<?php

namespace Tests\Candidates\Acceptance;

use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ListCandidatesTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function should_filter_unassigned_candidates(): void
    {
        $this->actingAsAdmin();

        $backendSpecialty = 'Backend';

        $this->postJson('/api/v1/candidates', [
            'name' => 'Assigned Candidate',
            'email' => 'assigned@example.com',
            'years_of_experience' => 5,
            'cv' => 'CV A',
            'primary_specialty' => $backendSpecialty,
        ]);

        $this->postJson('/api/v1/candidates', [
            'name' => 'Unassigned Candidate',
            'email' => 'unassigned@example.com',
            'years_of_experience' => 3,
            'cv' => 'CV B',
            'primary_specialty' => $backendSpecialty,
        ]);

        $this->postJson('/api/v1/evaluators', [
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

        $assignResponse = $this->postJson("/api/v1/evaluators/{$evaluatorId}/assign-candidate", [
            'candidate_id' => $candidateAssignedId,
        ]);

        $assignResponse->assertStatus(200);

        $response = $this->getJson('/api/v1/candidates?status=unassigned');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonFragment([
                'email' => 'unassigned@example.com',
            ]);
    }

    #[Test]
    public function should_filter_candidates_by_minimum_experience(): void
    {
        $this->actingAsAdmin();

        $this->postJson('/api/v1/candidates', [
            'name' => 'Junior',
            'email' => 'junior@example.com',
            'years_of_experience' => 1,
            'cv' => 'Junior CV',
        ]);

        $this->postJson('/api/v1/candidates', [
            'name' => 'Senior',
            'email' => 'senior@example.com',
            'years_of_experience' => 5,
            'cv' => 'Senior CV',
        ]);

        $response = $this->getJson('/api/v1/candidates?experience_min=2');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonFragment([
                'email' => 'senior@example.com',
            ]);
    }

    #[Test]
    public function should_search_candidates_by_partial_email(): void
    {
        $this->actingAsAdmin();

        $this->postJson('/api/v1/candidates', [
            'name' => 'Juan',
            'email' => 'juan@example.com',
            'years_of_experience' => 3,
            'cv' => 'CV Juan',
        ]);

        $this->postJson('/api/v1/candidates', [
            'name' => 'Ana',
            'email' => 'ana@example.com',
            'years_of_experience' => 4,
            'cv' => 'CV Ana',
        ]);

        $response = $this->getJson('/api/v1/candidates/search?email=juan@');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonFragment([
                'email' => 'juan@example.com',
            ]);
    }

    #[Test]
    public function should_search_candidates_by_cv_content_using_email_query_param(): void
    {
        $this->actingAsAdmin();

        $this->postJson('/api/v1/candidates', [
            'name' => 'CV Match',
            'email' => 'cv.match@example.com',
            'years_of_experience' => 5,
            'cv' => 'Expert in Laravel and microservices',
        ]);

        $this->postJson('/api/v1/candidates', [
            'name' => 'No Match',
            'email' => 'no.match@example.com',
            'years_of_experience' => 5,
            'cv' => 'React developer',
        ]);

        $response = $this->getJson('/api/v1/candidates/search?email=microservices');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonFragment([
                'email' => 'cv.match@example.com',
            ]);
    }

    #[Test]
    public function should_filter_candidates_by_primary_specialty(): void
    {
        $this->actingAsAdmin();

        $this->postJson('/api/v1/candidates', [
            'name' => 'Backend Dev',
            'email' => 'backend@example.com',
            'years_of_experience' => 4,
            'cv' => 'Backend CV',
            'primary_specialty' => 'Backend',
        ]);

        $this->postJson('/api/v1/candidates', [
            'name' => 'Frontend Dev',
            'email' => 'frontend@example.com',
            'years_of_experience' => 4,
            'cv' => 'Frontend CV',
            'primary_specialty' => 'Frontend',
        ]);

        $response = $this->getJson('/api/v1/candidates?specialty=Backend');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonFragment([
                'email' => 'backend@example.com',
            ]);
    }
}
