<?php

namespace Tests\Feature\Candidates;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Src\Candidates\Infrastructure\Jobs\AnalyzeCandidateCvJob;
use Tests\TestCase;

class AnalyzeCandidateAiTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function should_dispatch_analysis_job(): void
    {
        Queue::fake();

        $this->postJson('/api/candidates', [
            'name' => 'AI Candidate',
            'email' => 'ai.candidate@example.com',
            'years_of_experience' => 5,
            'cv' => 'Contenido del CV para análisis',
        ])->assertStatus(201);

        $model = \Src\Candidates\Infrastructure\Persistence\CandidateModel::where('email', 'ai.candidate@example.com')->firstOrFail();

        $this->postJson("/api/candidates/{$model->id}/analyze")
            ->assertStatus(202)
            ->assertJson([
                'status' => 'processing',
                'message' => 'Analysis queued',
            ]);

        Queue::assertPushed(AnalyzeCandidateCvJob::class, function (AnalyzeCandidateCvJob $job) use ($model) {
            return $job->candidateId === $model->id;
        });
    }

    #[Test]
    public function should_get_evaluation_pending_then_available(): void
    {
        $this->postJson('/api/candidates', [
            'name' => 'Eval Candidate',
            'email' => 'eval.candidate@example.com',
            'years_of_experience' => 3,
            'cv' => 'Contenido del CV',
        ])->assertStatus(201);

        $model = \Src\Candidates\Infrastructure\Persistence\CandidateModel::where('email', 'eval.candidate@example.com')->firstOrFail();

        $this->getJson("/api/candidates/{$model->id}/evaluation")
            ->assertStatus(202)
            ->assertJson([
                'status' => 'processing',
            ]);

        \Src\Candidates\Infrastructure\Persistence\CandidateEvaluationModel::create([
            'candidate_id' => $model->id,
            'summary' => 'Resumen de prueba',
            'skills' => ['PHP', 'Laravel'],
            'years_experience' => 3,
            'seniority_level' => 'Mid',
            'raw_response' => ['ok' => true],
            'created_at' => now()->toDateTimeString(),
        ]);

        $resp = $this->getJson("/api/candidates/{$model->id}/evaluation")
            ->assertStatus(200);

        /** @var array{data: array<string, mixed>} $json */
        $json = $resp->json();
        $this->assertEquals('Resumen de prueba', $json['data']['summary'] ?? null);
        $this->assertEquals(['PHP', 'Laravel'], $json['data']['skills'] ?? null);
        $this->assertEquals(3, $json['data']['years_experience'] ?? null);
        $this->assertEquals('Mid', $json['data']['seniority_level'] ?? null);
    }
}

