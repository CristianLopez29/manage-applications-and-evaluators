<?php

namespace Src\Evaluators\Infrastructure\Persistence;

use Src\Evaluators\Domain\CandidateAssignment;
use Src\Evaluators\Domain\Repositories\AssignmentRepository;
use Src\Evaluators\Domain\Enums\AssignmentStatus;

class EloquentAssignmentRepository implements AssignmentRepository
{
    public function save(CandidateAssignment $assignment): int
    {
        $model = CandidateAssignmentModel::create([
            'candidate_id' => $assignment->candidateId(),
            'evaluator_id' => $assignment->evaluatorId(),
            'status' => $assignment->status()->value,
            'assigned_at' => $assignment->assignedAt()->format('Y-m-d H:i:s'),
            'deadline' => $assignment->deadline()->format('Y-m-d H:i:s'),
            'last_reminder' => $assignment->lastReminder(),
        ]);

        return $model->id;
    }

    public function update(CandidateAssignment $assignment): void
    {
        if ($assignment->id() === null) {
            return;
        }

        CandidateAssignmentModel::where('id', $assignment->id())
            ->update([
                'status' => $assignment->status()->value,
                'deadline' => $assignment->deadline()->format('Y-m-d H:i:s'),
                'last_reminder' => $assignment->lastReminder(),
            ]);
    }

    public function findByCandidateId(int $candidateId): ?CandidateAssignment
    {
        $model = CandidateAssignmentModel::where('candidate_id', $candidateId)->first();

        if (!$model) {
            return null;
        }

        $assignedAt = $model->assigned_at instanceof \DateTimeInterface
            ? $model->assigned_at
            : new \DateTimeImmutable((string) $model->assigned_at);

        $deadlineSource = $model->deadline;
        if ($deadlineSource instanceof \DateTimeInterface) {
            $deadline = $deadlineSource;
        } else {
            $deadline = (clone $assignedAt)->modify('+7 days');
        }

        $lastReminderSource = $model->last_reminder;
        $lastReminder = $lastReminderSource instanceof \DateTimeInterface
            ? new \DateTimeImmutable($lastReminderSource->format('Y-m-d H:i:s'))
            : null;

        return CandidateAssignment::reconstruct(
            $model->id,
            $model->candidate_id,
            $model->evaluator_id,
            $model->status->value,
            new \DateTimeImmutable($assignedAt->format('Y-m-d H:i:s')),
            new \DateTimeImmutable($deadline->format('Y-m-d H:i:s')),
            $lastReminder
        );
    }

    /**
     * @return array<int, CandidateAssignment>
     */
    public function findByEvaluatorId(int $evaluatorId): array
    {
        $models = CandidateAssignmentModel::where('evaluator_id', $evaluatorId)->get();

        return $models->map(function (CandidateAssignmentModel $model) {
            $assignedAt = $model->assigned_at instanceof \DateTimeInterface
                ? $model->assigned_at
                : new \DateTimeImmutable((string) $model->assigned_at);

            $deadlineSource = $model->deadline;
            if ($deadlineSource instanceof \DateTimeInterface) {
                $deadline = $deadlineSource;
            } else {
                $deadline = (clone $assignedAt)->modify('+7 days');
            }

            $lastReminderSource = $model->last_reminder;
            $lastReminder = $lastReminderSource instanceof \DateTimeInterface
                ? new \DateTimeImmutable($lastReminderSource->format('Y-m-d H:i:s'))
                : null;

            return CandidateAssignment::reconstruct(
                $model->id,
                $model->candidate_id,
                $model->evaluator_id,
                $model->status->value,
                new \DateTimeImmutable($assignedAt->format('Y-m-d H:i:s')),
                new \DateTimeImmutable($deadline->format('Y-m-d H:i:s')),
                $lastReminder
            );
        })->all();
    }

    public function findByEvaluatorAndCandidate(int $evaluatorId, int $candidateId): ?CandidateAssignment
    {
        $model = CandidateAssignmentModel::where('evaluator_id', $evaluatorId)
            ->where('candidate_id', $candidateId)
            ->first();

        if (!$model) {
            return null;
        }

        $assignedAt = $model->assigned_at instanceof \DateTimeInterface
            ? $model->assigned_at
            : new \DateTimeImmutable((string) $model->assigned_at);

        $deadlineSource = $model->deadline;
        if ($deadlineSource instanceof \DateTimeInterface) {
            $deadline = $deadlineSource;
        } else {
            $deadline = (clone $assignedAt)->modify('+7 days');
        }

        $lastReminderSource = $model->last_reminder;
        $lastReminder = $lastReminderSource instanceof \DateTimeInterface
            ? new \DateTimeImmutable($lastReminderSource->format('Y-m-d H:i:s'))
            : null;

        return CandidateAssignment::reconstruct(
            $model->id,
            $model->candidate_id,
            $model->evaluator_id,
            $model->status->value,
            new \DateTimeImmutable($assignedAt->format('Y-m-d H:i:s')),
            new \DateTimeImmutable($deadline->format('Y-m-d H:i:s')),
            $lastReminder
        );
    }

    public function existsAssignment(int $candidateId, int $evaluatorId): bool
    {
        return CandidateAssignmentModel::where('candidate_id', $candidateId)
            ->where('evaluator_id', $evaluatorId)
            ->exists();
    }

    public function candidateHasActiveAssignment(int $candidateId): bool
    {
        return CandidateAssignmentModel::where('candidate_id', $candidateId)
            ->whereIn('status', [AssignmentStatus::PENDING, AssignmentStatus::IN_PROGRESS])
            ->exists();
    }

    public function deleteByEvaluatorAndCandidate(int $evaluatorId, int $candidateId): void
    {
        CandidateAssignmentModel::where('evaluator_id', $evaluatorId)
            ->where('candidate_id', $candidateId)
            ->delete();
    }
}
