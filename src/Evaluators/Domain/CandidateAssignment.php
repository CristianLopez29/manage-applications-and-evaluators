<?php

namespace Src\Evaluators\Domain;

use DateTimeImmutable;
use Src\Evaluators\Domain\ValueObjects\AssignmentStatus;

class CandidateAssignment
{
    private function __construct(
        private ?int $id,
        private int $candidateId,
        private int $evaluatorId,
        private AssignmentStatus $status,
        private DateTimeImmutable $assignedAt
    ) {
    }

    // Factory method to create a new assignment
    public static function create(
        int $candidateId,
        int $evaluatorId
    ): self {
        return new self(
            null, // ID is assigned when persisting
            $candidateId,
            $evaluatorId,
            AssignmentStatus::pending(),
            new DateTimeImmutable()
        );
    }

    // Factory method to reconstruct from persistence
    public static function reconstruct(
        int $id,
        int $candidateId,
        int $evaluatorId,
        string $status,
        DateTimeImmutable $assignedAt
    ): self {
        return new self(
            $id,
            $candidateId,
            $evaluatorId,
            AssignmentStatus::fromString($status),
            $assignedAt
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

    // Behavior methods
    public function startProgress(): void
    {
        $this->status = AssignmentStatus::inProgress();
    }

    public function complete(): void
    {
        $this->status = AssignmentStatus::completed();
    }

    public function reject(): void
    {
        $this->status = AssignmentStatus::rejected();
    }
}
