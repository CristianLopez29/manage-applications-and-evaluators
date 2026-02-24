<?php

namespace Src\Evaluators\Domain;

use DateTimeImmutable;
use Src\Evaluators\Domain\Enums\AssignmentStatus;

class CandidateAssignment
{
    private function __construct(
        private ?int $id,
        private int $candidateId,
        private int $evaluatorId,
        private AssignmentStatus $status,
        private DateTimeImmutable $assignedAt,
        private DateTimeImmutable $deadline,
        private ?DateTimeImmutable $lastReminder
    ) {
    }

    // Factory method to create a new assignment
    public static function create(
        int $candidateId,
        int $evaluatorId
    ): self {
        $assignedAt = new DateTimeImmutable();

        return new self(
            null, // ID is assigned when persisting
            $candidateId,
            $evaluatorId,
            AssignmentStatus::PENDING,
            $assignedAt,
            $assignedAt->modify('+7 days'),
            null
        );
    }

    // Factory method to reconstruct from persistence
    public static function reconstruct(
        int $id,
        int $candidateId,
        int $evaluatorId,
        string $status,
        DateTimeImmutable $assignedAt,
        DateTimeImmutable $deadline,
        ?DateTimeImmutable $lastReminder
    ): self {
        return new self(
            $id,
            $candidateId,
            $evaluatorId,
            AssignmentStatus::from($status),
            $assignedAt,
            $deadline,
            $lastReminder
        );
    }

    // Getters
    public function id(): ?int
    {
        return $this->id;
    }

    public function candidateId(): int
    {
        return $this->candidateId;
    }

    public function evaluatorId(): int
    {
        return $this->evaluatorId;
    }

    public function status(): AssignmentStatus
    {
        return $this->status;
    }

    public function assignedAt(): DateTimeImmutable
    {
        return $this->assignedAt;
    }

    public function deadline(): DateTimeImmutable
    {
        return $this->deadline;
    }

    public function lastReminder(): ?DateTimeImmutable
    {
        return $this->lastReminder;
    }

    // Behavior methods
    public function startProgress(): void
    {
        $this->status = AssignmentStatus::IN_PROGRESS;
    }

    public function complete(): void
    {
        $this->status = AssignmentStatus::COMPLETED;
    }

    public function reject(): void
    {
        $this->status = AssignmentStatus::REJECTED;
    }

    public function isOverdue(DateTimeImmutable $now): bool
    {
        return $now > $this->deadline;
    }

    public function updateLastReminder(DateTimeImmutable $when): void
    {
        $this->lastReminder = $when;
    }
}
