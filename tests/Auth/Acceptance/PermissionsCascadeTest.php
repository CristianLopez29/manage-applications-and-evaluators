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
        $this->actingAsAdmin();

        $this->postJson('/api/v1/evaluators', [
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

        $this->getJson("/api/v1/evaluators/{$evaluator->id}/candidates")
            ->assertStatus(200);
    }

    #[Test]
    public function candidate_can_view_their_own_summary_but_not_others(): void
    {
        $this->actingAsAdmin();

        $this->postJson('/api/v1/candidates', [
            'name' => 'Cand A',
            'email' => 'canda@example.com',
            'years_of_experience' => 3,
            'cv' => 'CV A',
            'primary_specialty' => 'Backend',
        ])->assertStatus(201);

        $this->postJson('/api/v1/candidates', [
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

        $this->getJson("/api/v1/candidates/{$candA->id}/summary")->assertStatus(200);
        $this->getJson("/api/v1/candidates/{$candB->id}/summary")->assertStatus(403);
    }

    /**
     * Regression guard: /analyze was the one candidate-scoped endpoint that checked only the
     * role (admin,candidate) and not ownership, unlike its summary/cv/evaluation siblings. Any
     * candidate account could trigger a billed AI analysis call for any other candidate's ID.
     */
    #[Test]
    public function candidate_can_request_analysis_for_their_own_cv_but_not_others(): void
    {
        $this->actingAsAdmin();

        $this->postJson('/api/v1/candidates', [
            'name' => 'Cand C',
            'email' => 'candc@example.com',
            'years_of_experience' => 3,
            'cv' => 'CV C',
            'primary_specialty' => 'Backend',
        ])->assertStatus(201);

        $this->postJson('/api/v1/candidates', [
            'name' => 'Cand D',
            'email' => 'candd@example.com',
            'years_of_experience' => 4,
            'cv' => 'CV D',
            'primary_specialty' => 'Backend',
        ])->assertStatus(201);

        $candC = \Src\Candidates\Infrastructure\Persistence\CandidateModel::where('email', 'candc@example.com')->firstOrFail();
        $candD = \Src\Candidates\Infrastructure\Persistence\CandidateModel::where('email', 'candd@example.com')->firstOrFail();

        $user = User::factory()->create([
            'email' => 'candidate.analyze@example.com',
            'role' => 'candidate',
            'candidate_id' => $candC->id,
        ]);
        Sanctum::actingAs($user, ['*']);

        $this->postJson("/api/v1/candidates/{$candD->id}/analyze")->assertStatus(403);
        $this->postJson("/api/v1/candidates/{$candC->id}/analyze")->assertStatus(202);
    }
}
