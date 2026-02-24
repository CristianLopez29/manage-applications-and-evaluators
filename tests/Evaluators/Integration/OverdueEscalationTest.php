<?php

namespace Tests\Evaluators\Integration;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use PHPUnit\Framework\Attributes\Test;
use Src\Evaluators\Infrastructure\Jobs\ProcessOverdueAssignmentsJob;
use Src\Evaluators\Infrastructure\Notifications\OverdueAssignmentEscalationNotification;
use Src\Evaluators\Infrastructure\Persistence\CandidateAssignmentModel;
use Tests\TestCase;

class OverdueEscalationTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_escalates_to_admins_when_overdue_exceeds_threshold(): void
    {
        Notification::fake();

        // Admin user
        $admin = User::factory()->create([
            'email' => 'admin@example.com',
            'role' => 'admin',
        ]);

        // Candidate & Evaluator
        $candidateId = DB::table('candidates')->insertGetId([
            'name' => 'Candidate X',
            'email' => 'candx@example.com',
            'years_of_experience' => 4,
            'cv_content' => 'CV',
            'primary_specialty' => 'Backend',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $evaluatorId = DB::table('evaluators')->insertGetId([
            'name' => 'Eval Y',
            'email' => 'evaly@example.com',
            'specialty' => 'Backend',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Overdue by 5 days
        CandidateAssignmentModel::create([
            'candidate_id' => $candidateId,
            'evaluator_id' => $evaluatorId,
            'status' => 'pending',
            'assigned_at' => now()->subDays(12),
            'deadline' => now()->subDays(5),
            'last_reminder' => null,
        ]);

        // Run job
        (new ProcessOverdueAssignmentsJob())->handle();

        Notification::assertSentTo(
            [$admin],
            OverdueAssignmentEscalationNotification::class
        );
    }
}

