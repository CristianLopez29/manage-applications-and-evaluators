<?php

declare(strict_types=1);

namespace Tests\Evaluators\Integration;

use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Src\Candidates\Infrastructure\Persistence\CandidateModel;
use Src\Evaluators\Infrastructure\Persistence\EvaluatorModel;
use Tests\TestCase;

class AssignmentHistoryTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function should_record_the_full_status_timeline_of_an_assignment(): void
    {
        $this->postJson('/api/v1/candidates', [
            'name' => 'History Candidate',
            'email' => 'history.candidate@example.com',
            'years_of_experience' => 6,
            'cv' => 'CV',
            'primary_specialty' => 'Backend',
        ])->assertStatus(201);

        $this->postJson('/api/v1/evaluators', [
            'name' => 'History Eval',
            'email' => 'history.eval@example.com',
            'specialty' => 'Backend',
        ])->assertStatus(201);

        $candidate = CandidateModel::first();
        $evaluator = EvaluatorModel::first();
        $this->assertNotNull($candidate);
        $this->assertNotNull($evaluator);

        $this->postJson("/api/v1/evaluators/{$evaluator->id}/assign-candidate", [
            'candidate_id' => $candidate->id,
        ])->assertStatus(200);

        $this->putJson("/api/v1/evaluators/{$evaluator->id}/assignments/{$candidate->id}/start-progress")
            ->assertStatus(200);

        $this->putJson("/api/v1/evaluators/{$evaluator->id}/assignments/{$candidate->id}/complete")
            ->assertStatus(200);

        $response = $this->getJson("/api/v1/candidates/{$candidate->id}/assignment-history");

        $response->assertStatus(200);
        /** @var array<int, array<string, mixed>> $timeline */
        $timeline = $response->json('data');

        $this->assertCount(3, $timeline);

        $this->assertNull($timeline[0]['from_status']);
        $this->assertSame('pending', $timeline[0]['to_status']);

        $this->assertSame('pending', $timeline[1]['from_status']);
        $this->assertSame('in_progress', $timeline[1]['to_status']);

        $this->assertSame('in_progress', $timeline[2]['from_status']);
        $this->assertSame('completed', $timeline[2]['to_status']);

        $this->assertSame($candidate->id, $timeline[0]['candidate_id']);
        $this->assertSame($evaluator->id, $timeline[0]['evaluator_id']);
    }

    #[Test]
    public function should_return_an_empty_timeline_for_a_candidate_without_assignments(): void
    {
        $this->postJson('/api/v1/candidates', [
            'name' => 'Lonely Candidate',
            'email' => 'lonely.candidate@example.com',
            'years_of_experience' => 2,
            'cv' => 'CV',
            'primary_specialty' => 'Frontend',
        ])->assertStatus(201);

        $candidate = CandidateModel::first();
        $this->assertNotNull($candidate);

        $response = $this->getJson("/api/v1/candidates/{$candidate->id}/assignment-history");

        $response->assertStatus(200);
        $this->assertSame([], $response->json('data'));
    }
}
