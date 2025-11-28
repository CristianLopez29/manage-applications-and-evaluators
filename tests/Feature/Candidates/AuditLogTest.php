<?php

namespace Tests\Feature\Candidates;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use PHPUnit\Framework\Attributes\Test;
use Src\Candidates\Domain\Events\CandidateRegistered;
use Tests\TestCase;

class AuditLogTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function should_dispatch_domain_event_when_candidate_is_registered(): void
    {
        Event::fake();

        $this->postJson('/api/candidates', [
            'name' => 'Audit Test',
            'email' => 'audit@example.com',
            'years_of_experience' => 5,
            'cv' => 'CV Content',
        ]);

        Event::assertDispatched(CandidateRegistered::class, function ($event) {
            return $event->email === 'audit@example.com';
        });
    }

    #[Test]
    public function should_log_action_when_event_is_dispatched(): void
    {
        Log::spy();

        $this->postJson('/api/candidates', [
            'name' => 'Log Test',
            'email' => 'log@example.com',
            'years_of_experience' => 5,
            'cv' => 'CV Content',
        ]);

        Log::shouldHaveReceived('info')
            ->once()
            ->withArgs(function ($message, $context) {
                return $message === 'New Candidate Registered' &&
                    $context['payload']['email'] === 'log@example.com';
            });

    }
}
