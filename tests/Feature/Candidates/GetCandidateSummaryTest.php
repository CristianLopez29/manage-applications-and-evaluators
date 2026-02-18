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
        $candidate = \Src\Candidates\Infrastructure\Persistence\CandidateModel::first();
        $this->assertNotNull($candidate);
        $candidateId = $candidate->id;

        // 2. Create Evaluator
        $this->postJson('/api/evaluators', [
            'name' => 'María González',
            'email' => 'maria@example.com',
            'specialty' => 'Backend',
        ]);
        $evaluator = \Src\Evaluators\Infrastructure\Persistence\EvaluatorModel::first();
        $this->assertNotNull($evaluator);
        $evaluatorId = $evaluator->id;

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

        /** @var array{candidate_info: array{name: string}, assignment_info: array{evaluator_name: string, status: string}, compliance_report: array<string, string>} $data */
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
        $candidate = \Src\Candidates\Infrastructure\Persistence\CandidateModel::first();
        $this->assertNotNull($candidate);
        $candidateId = $candidate->id;

        $response = $this->getJson("/api/candidates/{$candidateId}/summary");

        $response->assertStatus(200);
        $this->assertEquals('Unassigned', $response->json('data.assignment_info'));
    }

    #[Test]
    public function should_include_pdf_flag_and_download_url_when_pdf_is_present(): void
    {
        $file = \Illuminate\Http\UploadedFile::fake()->create('cv.pdf', 50, 'application/pdf');

        $this->post('/api/candidates', [
            'name' => 'PDF Summ',
            'email' => 'pdf.summ@example.com',
            'years_of_experience' => 3,
            'cv_file' => $file,
        ])->assertStatus(201);

        $candidate = \Src\Candidates\Infrastructure\Persistence\CandidateModel::where('email', 'pdf.summ@example.com')->firstOrFail();

        $res = $this->getJson("/api/candidates/{$candidate->id}/summary")->assertStatus(200);
        $this->assertTrue($res->json('data.candidate_info.cv_pdf'));
        $this->assertNotEmpty($res->json('data.candidate_info.cv_download_url'));
    }
    #[Test]
    public function should_return_404_if_candidate_not_found(): void
    {
        $response = $this->getJson("/api/candidates/999/summary");

        $response->assertStatus(404);
    }
}
