<?php

declare(strict_types=1);

namespace Tests\Candidates\Acceptance;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Src\Candidates\Infrastructure\Jobs\AnalyzeCandidateCvJob;
use Src\Candidates\Infrastructure\Persistence\CandidateModel;
use Tests\TestCase;

/**
 * AiUsageBudget caps how many billed AI calls the whole application makes per day,
 * independent of caller, role, or candidate id — the backstop against the per-candidate
 * dedup (AnalyzeCandidateCvJobTest) still allowing unbounded total spend across many
 * distinct candidates.
 *
 * The primary trigger for a billed call is registration itself: RegisterCandidacyController
 * calls RequestCandidateAnalysis for every new candidate, not just POST /analyze. Both
 * controllers share the same use case, so the budget check at that one shared point covers
 * both — these tests exercise the registration path deliberately rather than only the
 * explicit /analyze endpoint, since that is the real, unbounded, unauthenticated-role-gated
 * (any admin or candidate account) cost vector.
 */
class AnalyzeCandidateBudgetTest extends TestCase
{
    use RefreshDatabase;

    private function registerCandidate(string $email): CandidateModel
    {
        $this->postJson('/api/v1/candidates', [
            'name' => 'Budget Candidate',
            'email' => $email,
            'years_of_experience' => 3,
            'cv' => 'CV',
        ])->assertStatus(201);

        return CandidateModel::where('email', $email)->firstOrFail();
    }

    #[Test]
    public function should_queue_analysis_on_registration_while_under_the_daily_budget(): void
    {
        config(['ai.daily_call_budget' => 5]);
        $this->actingAsAdmin();
        Queue::fake();

        $response = $this->postJson('/api/v1/candidates', [
            'name' => 'Under Budget',
            'email' => 'under.budget@example.com',
            'years_of_experience' => 3,
            'cv' => 'CV',
        ])->assertStatus(201);

        $this->assertSame('processing', $response->json('data.analysis_status'));
        Queue::assertPushed(AnalyzeCandidateCvJob::class, 1);
    }

    /**
     * Registration always returns 201 even when the AI queueing step fails —
     * RegisterCandidacyController catches every \Throwable from RequestCandidateAnalysis and
     * reports it as analysis_status rather than failing the candidacy itself. A budget
     * rejection must surface there, not as a registration failure.
     */
    #[Test]
    public function should_register_successfully_but_skip_analysis_once_the_budget_is_spent(): void
    {
        config(['ai.daily_call_budget' => 1]);
        $this->actingAsAdmin();
        Queue::fake();

        $this->registerCandidate('spends.the.budget@example.com');

        $response = $this->postJson('/api/v1/candidates', [
            'name' => 'Over Budget',
            'email' => 'over.budget@example.com',
            'years_of_experience' => 3,
            'cv' => 'CV',
        ])->assertStatus(201);

        $this->assertSame('failed_to_queue', $response->json('data.analysis_status'));
        Queue::assertPushed(AnalyzeCandidateCvJob::class, 1);
    }

    #[Test]
    public function should_reject_an_explicit_reanalyze_once_the_daily_budget_is_spent(): void
    {
        config(['ai.daily_call_budget' => 1]);
        $this->actingAsAdmin();
        Queue::fake();

        // The registration itself spends the day's only slot.
        $candidate = $this->registerCandidate('explicit.retry@example.com');

        // A different, already-registered candidate — proves the cap is global rather than
        // tied to the one that happened to trigger it.
        $another = $this->registerCandidate('another.candidate@example.com');

        $this->postJson("/api/v1/candidates/{$another->id}/analyze")
            ->assertStatus(429)
            ->assertJsonStructure(['message']);

        // Only the very first registration's auto-triggered analysis ever reached the queue.
        Queue::assertPushed(AnalyzeCandidateCvJob::class, 1);
        Queue::assertPushed(AnalyzeCandidateCvJob::class, function (AnalyzeCandidateCvJob $job) use ($candidate) {
            return $job->candidateId === $candidate->id;
        });
    }
}
