<?php

namespace Tests\Evaluators\Integration;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use PHPUnit\Framework\Attributes\Test;
use Src\Evaluators\Infrastructure\Notifications\CandidateAssignedNotification;
use Tests\TestCase;

class AssignmentNotificationTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function should_send_notifications_when_candidate_is_assigned(): void
    {
        Notification::fake();

        $this->postJson('/api/candidates', [
            'name' => 'Juan Pérez',
            'email' => 'juan@example.com',
            'years_of_experience' => 5,
            'cv' => 'Experiencia en desarrollo backend...',
            'primary_specialty' => 'Backend',
        ]);

        $this->postJson('/api/evaluators', [
            'name' => 'María González',
            'email' => 'maria@example.com',
            'specialty' => 'Backend',
        ]);

        $candidate = \Src\Candidates\Infrastructure\Persistence\CandidateModel::first();
        $this->assertNotNull($candidate);
        $candidateId = $candidate->id;

        $evaluator = \Src\Evaluators\Infrastructure\Persistence\EvaluatorModel::first();
        $this->assertNotNull($evaluator);
        $evaluatorId = $evaluator->id;

        $this->postJson("/api/evaluators/{$evaluatorId}/assign-candidate", [
            'candidate_id' => $candidateId,
        ])->assertStatus(200);

        Notification::assertSentOnDemand(CandidateAssignedNotification::class, function (CandidateAssignedNotification $notification, array $channels, $notifiable) {
            $mail = $notifiable->routeNotificationFor('mail');

            return in_array('mail', $channels, true)
                && $mail === 'maria@example.com';
        });

        Notification::assertSentOnDemand(CandidateAssignedNotification::class, function (CandidateAssignedNotification $notification, array $channels, $notifiable) {
            $mail = $notifiable->routeNotificationFor('mail');

            return in_array('mail', $channels, true)
                && $mail === 'juan@example.com';
        });
    }
}

