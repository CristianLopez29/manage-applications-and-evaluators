<?php

namespace Src\Evaluators\Infrastructure\Listeners;

use Src\Evaluators\Domain\Events\CandidateAssigned;
use Src\Shared\Domain\Audit\AuditLogger;

class LogCandidateAssignment
{
    public function __construct(
        private readonly AuditLogger $auditLogger
    ) {
    }

    public function handle(CandidateAssigned $event): void
    {
        $this->auditLogger->log(
            action: 'Candidate Assigned to Evaluator',
            entityType: 'CandidateAssignment',
            entityId: (string) $event->assignmentId,
            payload: [
                'candidate_id' => $event->candidateId,
                'evaluator_id' => $event->evaluatorId,
                'occurred_at' => $event->occurredOn()->format('Y-m-d H:i:s')
            ]
        );
    }
}
