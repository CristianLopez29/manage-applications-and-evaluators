<?php

namespace Tests\Evaluators\Unit;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use PHPUnit\Framework\Attributes\Test;
use Src\Evaluators\Infrastructure\Jobs\ProcessOverdueAssignmentsJob;
use Src\Evaluators\Infrastructure\Notifications\OverdueAssignmentNotification;
use Src\Evaluators\Infrastructure\Persistence\CandidateAssignmentModel;
use Tests\TestCase;

class ProcessOverdueAssignmentsJobTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function should_send_notifications_and_update_last_reminder_for_overdue_assignments(): void
    {
        Notification::fake();

        $candidateId = \Illuminate\Support\Facades\DB::table('candidates')->insertGetId([
            'name' => 'Overdue Candidate',
            'email' => 'overdue.candidate@example.com',
            'years_of_experience' => 5,
            'cv_content' => 'CV Content',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $evaluatorId = \Illuminate\Support\Facades\DB::table('evaluators')->insertGetId([
            'name' => 'Overdue Evaluator',
            'email' => 'overdue.evaluator@example.com',
            'specialty' => 'Backend',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        CandidateAssignmentModel::create([
            'candidate_id' => $candidateId,
            'evaluator_id' => $evaluatorId,
            'status' => 'pending',
            'assigned_at' => now()->subDays(10),
            'deadline' => now()->subDays(3),
            'last_reminder' => null,
        ]);

        (new ProcessOverdueAssignmentsJob())->handle();

        Notification::assertSentOnDemand(OverdueAssignmentNotification::class, function (OverdueAssignmentNotification $notification, array $channels, $notifiable) {
            $mail = $notifiable->routeNotificationFor('mail');

            return in_array('mail', $channels, true)
                && ($mail === 'overdue.evaluator@example.com' || $mail === 'overdue.candidate@example.com');
        });

        $this->assertDatabaseMissing('candidate_assignments', [
            'candidate_id' => $candidateId,
            'evaluator_id' => $evaluatorId,
            'last_reminder' => null,
        ]);
    }
}

