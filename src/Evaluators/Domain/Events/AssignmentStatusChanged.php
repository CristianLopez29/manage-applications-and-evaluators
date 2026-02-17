<?php

namespace Src\Evaluators\Domain\Events;

use DateTimeImmutable;

class AssignmentStatusChanged
{
    public function __construct(
        public readonly int $assignmentId,
        public readonly int $candidateId,
        public readonly int $evaluatorId,
        public readonly string $previousStatus,
        public readonly string $newStatus,
        public readonly DateTimeImmutable $occurredOn
    ) {
    }

    public function occurredOn(): DateTimeImmutable
    {
        return $this->occurredOn;
    }
}
