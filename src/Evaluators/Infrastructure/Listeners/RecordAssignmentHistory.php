<?php

declare(strict_types=1);

namespace Src\Evaluators\Infrastructure\Listeners;

use Src\Evaluators\Domain\Enums\AssignmentStatus;
use Src\Evaluators\Domain\Events\AssignmentStatusChanged;
use Src\Evaluators\Domain\Events\CandidateAssigned;
use Src\Evaluators\Domain\Repositories\AssignmentHistoryRepository;

class RecordAssignmentHistory
{
    public function __construct(
        private readonly AssignmentHistoryRepository $history
    ) {
    }

    public function handleAssigned(CandidateAssigned $event): void
    {
        $this->history->record(
            $event->assignmentId,
            $event->candidateId,
            $event->evaluatorId,
            null,
            AssignmentStatus::PENDING->value,
            $event->occurredOn()
        );
    }

    public function handleStatusChanged(AssignmentStatusChanged $event): void
    {
        $this->history->record(
            $event->assignmentId,
            $event->candidateId,
            $event->evaluatorId,
            $event->previousStatus,
            $event->newStatus,
            $event->occurredOn()
        );
    }
}
