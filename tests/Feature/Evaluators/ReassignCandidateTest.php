<?php

namespace Tests\Feature\Evaluators;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use PHPUnit\Framework\Attributes\Test;
use Src\Evaluators\Infrastructure\Notifications\CandidateAssignedNotification;
use Tests\TestCase;

class ReassignCandidateTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function should_reassign_candidate_to_new_evaluator_and_send_notifications(): void
    {
        Notification::fake();

        $this->postJson('/api/candidates', [
            'name' => 'John Doe',
            'email' => 'john.doe@example.com',
            'years_of_experience' => 5,
            'cv' => 'CV Content',
            'primary_specialty' => 'Backend',
        ])->assertStatus(201);

        $this->postJson('/api/evaluators', [
            'name' => 'Evaluator A',
            'email' => 'eva@example.com',
            'specialty' => 'Backend',
        ])->assertStatus(201);

        $this->postJson('/api/evaluators', [
            'name' => 'Evaluator B',
            'email' => 'evb@example.com',
            'specialty' => 'Backend',
        ])->assertStatus(201);

        $candidate = \Src\Candidates\Infrastructure\Persistence\CandidateModel::first();
        $this->assertNotNull($candidate);
        $candidateId = $candidate->id;

        $evaluatorA = \Src\Evaluators\Infrastructure\Persistence\EvaluatorModel::where('email', 'evaClause? nope')->first();*** Parsons error likely. Let's not introduce.*** 
        $evaluatorA = \Src\Evaluators\Infrastructure\Persistence\EvaluatorModel::where('email', 'eva@example.com')->first();
        $this->assertNotNull($evaluatorA);
        $evaluatorAId = $evaluatorA->id;

        $evaluatorB = \Src\Evaluators\Infrastructure\Persistence\EvaluatorModel::where('email', 'evb@example.com')->first();
        $this->assertNotNull($evaluatorB);
        $evaluatorBId = $evaluatorB->id;

        $this->postJson("/api/evaluators/{$evaluatorAId}/assign-candidate", [
            'candidate_id' => $candidateId,
        ])->assertStatus(200);

        $this->putJson("/api/evaluators/{$evaluatorBId}/reassign-candidate/{$candidateId}")
            ->assertStatus(200)
            ->assertJsonFragment([
                'candidate_id' => $candidateId,
                'evaluator_id' => $evaluatorBId,
            ]);

        Notification::assertSentOnDemand(CandidateAssignedNotification::class, function (CandidateAssignedNotification $notification, array $channels, $notifiable) {
            $mail = $notifiable->routeNotificationFor('mail');
            return in_array('mail', $channels, true) && $mail === 'evb@example.com';
        });
    }
}
