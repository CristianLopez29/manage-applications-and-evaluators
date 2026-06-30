<?php

declare(strict_types=1);

namespace Src\Evaluators\Domain\Repositories;

use DateTimeImmutable;

interface AssignmentHistoryRepository
{
    public function record(
        int $assignmentId,
        int $candidateId,
        int $evaluatorId,
        ?string $fromStatus,
        string $toStatus,
        DateTimeImmutable $occurredAt
    ): void;

    /**
     * Chronological status timeline for a candidate's assignments.
     *
     * @return array<int, array<string, mixed>>
     */
    public function findByCandidateId(int $candidateId): array;
}
