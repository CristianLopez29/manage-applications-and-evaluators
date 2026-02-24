<?php

namespace Tests\Evaluators\Integration;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use PHPUnit\Framework\Attributes\Test;
use Src\Evaluators\Infrastructure\Notifications\AssignmentStatusChangedNotification;
use Tests\TestCase;

class AssignmentStatusChangeNotificationTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function should_send_notifications_on_status_change_to_in_progress_and_completed(): void
    {
        Notification::fake();

        $this->postJson('/api/candidates', [
            'name' => 'Status Candidate',
            'email' => 'status.candidate@example.com',
            'years_of_experience' => 6,
            'cv' => 'CV',
            'primary_specialty' => 'Backend',
        ])->assertStatus(201);

        $this->postJson('/api/evaluators', [
            'name' => 'Status Eval',
            'email' => 'status.eval@example.com',
            'specialty' => 'Backend',
        ])->assertStatus(201);

        $candidate = \Src\Candidates\Infrastructure\Persistence\CandidateModel::first();
        $evaluator = \Src\Evaluators\Infrastructure\Persistence\EvaluatorModel::first();
        $this->assertNotNull($candidate);
        $this->assertNotNull($evaluator);

        $this->postJson("/api/evaluators/{$evaluator->id}/assign-candidate", [
            'candidate_id' => $candidate->id,
        ])->assertStatus(200);

        $this->putJson("/api/evaluators/{$evaluator->id}/assignments/{$candidate->id}/start-progress")
            ->assertStatus(200);

        Notification::assertSentOnDemand(AssignmentStatusChangedNotification::class, function (AssignmentStatusChangedNotification $notification, array $channels, $notifiable) {
            $mail = $notifiable->routeNotificationFor('mail');
            return in_array('mail', $channels, true) && in_array($mail, ['status.eval@example.com', 'status.candidate@example.com'], true);
        });

        $this->putJson("/api/evaluators/{$evaluator->id}/assignments/{$candidate->id}/complete")
            ->assertStatus(200);

        Notification::assertSentOnDemand(AssignmentStatusChangedNotification::class);
    }
}
