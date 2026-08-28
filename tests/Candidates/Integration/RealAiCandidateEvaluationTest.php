<?php

namespace Tests\Candidates\Integration;

use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Src\Candidates\Infrastructure\Persistence\CandidateModel;
use Tests\TestCase;

class RealAiCandidateEvaluationTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function integration_ai_parsing_with_gemini_for_real_candidate(): void
    {
        // Opt-in on purpose: this test calls the real Gemini API, so it costs money and
        // depends on a third party being up. Enable it with RUN_AI_INTEGRATION_TESTS=true
        // plus a GEMINI_API_KEY. Both guards run before any setup, so a skipped run is free.
        $runExternal = config('app.run_ai_integration_tests');
        if (!$runExternal) {
            $this->markTestSkipped('Set RUN_AI_INTEGRATION_TESTS=true to run the external AI integration test.');
        }

        $apiKey = env('GEMINI_API_KEY');
        if (!is_string($apiKey) || $apiKey === '') {
            $this->markTestSkipped('GEMINI_API_KEY is not configured.');
        }

        $this->actingAsAdmin();

        config()->set('queue.default', 'sync');
        putenv('AI_PROVIDER=gemini');

        $email = 'real.ai.candidate@example.com';

        $this->postJson('/api/v1/candidates', [
            'name' => 'AI Real Candidate',
            'email' => $email,
            'years_of_experience' => 8,
            'cv' => 'Backend engineer with 8 years of experience in PHP, Laravel, MySQL and Redis. '
                . 'Designed and maintained hexagonal architecture services, led small teams, and implemented CI/CD pipelines.',
        ])->assertStatus(201);

        $candidate = CandidateModel::where('email', $email)->firstOrFail();

        $this->postJson("/api/v1/candidates/{$candidate->id}/analyze")
            ->assertStatus(202)
            ->assertJson([
                'status' => 'processing',
            ]);

        $response = $this->getJson("/api/v1/candidates/{$candidate->id}/evaluation");

        $response->assertStatus(200);

        /** @var array<string, mixed>|null $data */
        $data = $response->json('data');

        $this->assertIsArray($data);
        $this->assertArrayHasKey('candidate_id', $data);
        $this->assertEquals($candidate->id, $data['candidate_id']);

        $this->assertArrayHasKey('summary', $data);
        $this->assertIsString($data['summary']);
        $this->assertNotSame('', trim($data['summary']));

        $this->assertArrayHasKey('skills', $data);
        if ($data['skills'] !== null) {
            $this->assertIsArray($data['skills']);
        }

        $this->assertArrayHasKey('years_experience', $data);
        if ($data['years_experience'] !== null) {
            $this->assertIsInt($data['years_experience']);
        }

        $this->assertArrayHasKey('seniority_level', $data);
        if ($data['seniority_level'] !== null) {
            $this->assertIsString($data['seniority_level']);
        }

        $this->assertArrayHasKey('analyzed_at', $data);
        $this->assertNotNull($data['analyzed_at']);
    }
}
