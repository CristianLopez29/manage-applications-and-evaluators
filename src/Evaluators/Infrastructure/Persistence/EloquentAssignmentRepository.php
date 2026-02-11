<?php

namespace Src\Evaluators\Infrastructure\Persistence;

use Src\Evaluators\Domain\CandidateAssignment;
use Src\Evaluators\Domain\Repositories\AssignmentRepository;

class EloquentAssignmentRepository implements AssignmentRepository
{
    public function save(CandidateAssignment $assignment): int
    {
        $model = CandidateAssignmentModel::create([
            'candidate_id' => $assignment->candidateId(),
            'evaluator_id' => $assignment->evaluatorId(),
            'status' => $assignment->status()->value(),
            'assigned_at' => $assignment->assignedAt()->format('Y-m-d H:i:s'),
        ]);

        return $model->id;
    }

    public function findByCandidateId(int $candidateId): ?CandidateAssignment
    {
        $model = CandidateAssignmentModel::where('candidate_id', $candidateId)->first();

        if (!$model) {
            return null;
        }

        return CandidateAssignment::reconstruct(
            $model->id,
            $model->candidate_id,
            $model->evaluator_id,
            $model->status,
            new \DateTimeImmutable($model->assigned_at)
        );
    }

    /**
     * @return array<int, CandidateAssignment>
     */
    public function findByEvaluatorId(int $evaluatorId): array
    {
        $models = CandidateAssignmentModel::where('evaluator_id', $evaluatorId)->get();

        return $models->map(function (CandidateAssignmentModel $model) {
            return CandidateAssignment::reconstruct(
                $model->id,
                $model->candidate_id,
                $model->evaluator_id,
                $model->status,
                new \DateTimeImmutable($model->assigned_at->format('Y-m-d H:i:s'))
            );
        })->toArray();
    }

    public function existsAssignment(int $candidateId, int $evaluatorId): bool
    {
        return CandidateAssignmentModel::where('candidate_id', $candidateId)
            ->where('evaluator_id', $evaluatorId)
            ->exists();
    }

    public function candidateHasActiveAssignment(int $candidateId): bool
    {
        return CandidateAssignmentModel::where('candidate_id', $candidateId)->exists();
    }
}
