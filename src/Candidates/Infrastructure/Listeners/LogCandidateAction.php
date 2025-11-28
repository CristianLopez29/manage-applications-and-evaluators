<?php

namespace Src\Candidates\Infrastructure\Listeners;

use Illuminate\Support\Facades\Log;
use Src\Candidates\Domain\Events\CandidateRegistered;

class LogCandidateAction
{
    public function __construct(
        private readonly \Src\Shared\Domain\Audit\AuditLogger $auditLogger
    ) {
    }

    public function handle(CandidateRegistered $event): void
    {
        $this->auditLogger->log(
            action: 'New Candidate Registered',
            entityType: 'Candidate',
            entityId: (string) $event->candidateId,
            payload: [
                'email' => $event->email,
                'occurred_at' => $event->occurredOn()->format('Y-m-d H:i:s')
            ]
        );
    }
}
