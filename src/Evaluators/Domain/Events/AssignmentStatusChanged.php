<?php

declare(strict_types=1);

namespace Src\Evaluators\Domain\Events;

use DateTimeImmutable;
use Src\Shared\Domain\DomainEvent;

class AssignmentStatusChanged implements DomainEvent
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
