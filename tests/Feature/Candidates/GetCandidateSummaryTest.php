<?php

namespace Tests\Feature\Candidates;

use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class GetCandidateSummaryTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function should_return_candidate_summary_with_assignment_and_validations(): void
    {
        // 1. Create Candidate
        $this->postJson('/api/candidates', [
            'name' => 'Juan Pérez',
            'email' => 'juan@example.com',
            'years_of_experience' => 5,
            'cv' => 'CV Content',
        ]);
        $candidateId = \Src\Candidates\Infrastructure\Persistence\CandidateModel::first()->id;

        // 2. Create Evaluator
        $this->postJson('/api/evaluators', [
            'name' => 'María González',
            'email' => 'maria@example.com',
            'specialty' => 'Backend',
        ]);
        $evaluatorId = \Src\Evaluators\Infrastructure\Persistence\EvaluatorModel::first()->id;

        // 3. Assign
        $this->postJson("/api/evaluators/{$evaluatorId}/assign-candidate", ['candidate_id' => $candidateId]);

        // 4. Get Summary
        $response = $this->getJson("/api/candidates/{$candidateId}/summary");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'candidate_info',
                    'assignment_info',
                    'compliance_report'
                ]
            ]);

        $data = $response->json('data');

        // Verify Candidate Info
        $this->assertEquals('Juan Pérez', $data['candidate_info']['name']);

        // Verify Assignment Info
        $this->assertEquals('María González', $data['assignment_info']['evaluator_name']);
        $this->assertEquals('pending', $data['assignment_info']['status']);

        // Verify Compliance Report
        $this->assertEquals('Passed', $data['compliance_report']['CV Required']);
        $this->assertEquals('Passed', $data['compliance_report']['Valid Email']);
        $this->assertEquals('Passed', $data['compliance_report']['Minimum Experience']);
    }

    #[Test]
    public function should_return_summary_without_assignment_if_not_assigned(): void
    {
        // Create only candidate
        $this->postJson('/api/candidates', [
            'name' => 'Ana García',
            'email' => 'ana@example.com',
            'years_of_experience' => 3,
            'cv' => 'CV Content',
        ]);
        $candidateId = \Src\Candidates\Infrastructure\Persistence\CandidateModel::first()->id;

        $response = $this->getJson("/api/candidates/{$candidateId}/summary");

        $response->assertStatus(200);
        $this->assertEquals('Unassigned', $response->json('data.assignment_info'));
    }

    #[Test]
    public function should_return_404_if_candidate_not_found(): void
    {
        $response = $this->getJson("/api/candidates/999/summary");

        $response->assertStatus(404);
    }
}
