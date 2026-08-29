<?php

declare(strict_types=1);

namespace Tests\Evaluators\Acceptance;

use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Src\Candidates\Infrastructure\Persistence\CandidateModel;
use Src\Evaluators\Infrastructure\Persistence\EvaluatorModel;
use Tests\TestCase;

/**
 * Rejection was the one assignment transition with no test: the use case, its controller
 * and the 404 branch all shipped unverified while sibling transitions (assign, reassign,
 * unassign, complete) were covered.
 */
class RejectAssignmentTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array{evaluatorId: int, candidateId: int}
     */
    private function assignCandidateToEvaluator(): array
    {
        $this->postJson('/api/v1/candidates', [
            'name' => 'Reject Me',
            'email' => 'reject.me@example.com',
            'years_of_experience' => 4,
            'cv' => 'CV',
            'primary_specialty' => 'Backend',
        ])->assertStatus(201);

        $this->postJson('/api/v1/evaluators', [
            'name' => 'Eval R',
            'email' => 'eval.r@example.com',
            'specialty' => 'Backend',
        ])->assertStatus(201);

        $candidate = CandidateModel::firstOrFail();
        $evaluator = EvaluatorModel::firstOrFail();

        $this->postJson("/api/v1/evaluators/{$evaluator->id}/assign-candidate", [
            'candidate_id' => $candidate->id,
        ])->assertStatus(200);

        return ['evaluatorId' => (int) $evaluator->id, 'candidateId' => (int) $candidate->id];
    }

    #[Test]
    public function should_reject_an_assignment(): void
    {
        $this->actingAsAdmin();
        ['evaluatorId' => $evaluatorId, 'candidateId' => $candidateId] = $this->assignCandidateToEvaluator();

        $this->putJson("/api/v1/evaluators/{$evaluatorId}/assignments/{$candidateId}/reject")
            ->assertStatus(200)
            ->assertJson(['message' => 'Assignment rejected']);

        $this->assertDatabaseHas('candidate_assignments', [
            'candidate_id' => $candidateId,
            'evaluator_id' => $evaluatorId,
            'status' => 'rejected',
        ]);
    }

    /**
     * The status change must reach the history trail, which is written by the
     * AssignmentStatusChanged listener rather than by the use case itself.
     */
    #[Test]
    public function should_record_the_transition_in_the_assignment_history(): void
    {
        $this->actingAsAdmin();
        ['evaluatorId' => $evaluatorId, 'candidateId' => $candidateId] = $this->assignCandidateToEvaluator();

        $this->putJson("/api/v1/evaluators/{$evaluatorId}/assignments/{$candidateId}/reject")
            ->assertStatus(200);

        $this->assertDatabaseHas('assignment_history', [
            'candidate_id' => $candidateId,
            'evaluator_id' => $evaluatorId,
            'from_status' => 'pending',
            'to_status' => 'rejected',
        ]);
    }

    #[Test]
    public function should_return_404_when_the_assignment_does_not_exist(): void
    {
        $this->actingAsAdmin();

        $this->putJson('/api/v1/evaluators/9999/assignments/8888/reject')
            ->assertStatus(404)
            ->assertJsonStructure(['error']);
    }

    #[Test]
    public function should_require_authentication(): void
    {
        $this->putJson('/api/v1/evaluators/1/assignments/1/reject')
            ->assertStatus(401);
    }
}
