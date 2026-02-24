<?php

namespace Tests\Auth\Acceptance;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PermissionsCascadeTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function evaluator_can_view_their_own_candidates_list(): void
    {
        $this->postJson('/api/evaluators', [
            'name' => 'Eval P',
            'email' => 'evalp@example.com',
            'specialty' => 'Backend',
        ])->assertStatus(201);

        $evaluator = \Src\Evaluators\Infrastructure\Persistence\EvaluatorModel::firstOrFail();

        $user = User::factory()->create([
            'email' => 'evaluator.user@example.com',
            'role' => 'evaluator',
            'evaluator_id' => $evaluator->id,
        ]);

        Sanctum::actingAs($user, ['*']);

        $this->getJson("/api/evaluators/{$evaluator->id}/candidates")
            ->assertStatus(200);
    }

    #[Test]
    public function candidate_can_view_their_own_summary_but_not_others(): void
    {
        $this->postJson('/api/candidates', [
            'name' => 'Cand A',
            'email' => 'canda@example.com',
            'years_of_experience' => 3,
            'cv' => 'CV A',
            'primary_specialty' => 'Backend',
        ])->assertStatus(201);

        $this->postJson('/api/candidates', [
            'name' => 'Cand B',
            'email' => 'candb@example.com',
            'years_of_experience' => 4,
            'cv' => 'CV B',
            'primary_specialty' => 'Backend',
        ])->assertStatus(201);

        $candA = \Src\Candidates\Infrastructure\Persistence\CandidateModel::where('email', 'canda@example.com')->firstOrFail();
        $candB = \Src\Candidates\Infrastructure\Persistence\CandidateModel::where('email', 'candb@example.com')->firstOrFail();

        $user = User::factory()->create([
            'email' => 'candidate.user@example.com',
            'role' => 'candidate',
            'candidate_id' => $candA->id,
        ]);
        Sanctum::actingAs($user, ['*']);

        $this->getJson("/api/candidates/{$candA->id}/summary")->assertStatus(200);
        $this->getJson("/api/candidates/{$candB->id}/summary")->assertStatus(403);
    }
}
