<?php

declare(strict_types=1);

namespace Src\Evaluators\Infrastructure\Persistence;

use DateTimeImmutable;
use Src\Evaluators\Domain\Repositories\AssignmentHistoryRepository;

class EloquentAssignmentHistoryRepository implements AssignmentHistoryRepository
{
    public function record(
        int $assignmentId,
        int $candidateId,
        int $evaluatorId,
        ?string $fromStatus,
        string $toStatus,
        DateTimeImmutable $occurredAt
    ): void {
        AssignmentHistoryModel::create([
            'assignment_id' => $assignmentId,
            'candidate_id' => $candidateId,
            'evaluator_id' => $evaluatorId,
            'from_status' => $fromStatus,
            'to_status' => $toStatus,
            'occurred_at' => $occurredAt->format('Y-m-d H:i:s'),
        ]);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function findByCandidateId(int $candidateId): array
    {
        return AssignmentHistoryModel::query()
            ->where('candidate_id', $candidateId)
            ->orderBy('occurred_at')
            ->orderBy('id')
            ->get()
            ->map(fn (AssignmentHistoryModel $row): array => [
                'assignment_id' => $row->assignment_id,
                'candidate_id' => $row->candidate_id,
                'evaluator_id' => $row->evaluator_id,
                'from_status' => $row->from_status,
                'to_status' => $row->to_status,
                'occurred_at' => $row->occurred_at->format('Y-m-d H:i:s'),
            ])
            ->all();
    }
}
