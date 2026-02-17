<?php

namespace Src\Evaluators\Application;

use Src\Evaluators\Domain\Exceptions\AssignmentException;
use Src\Evaluators\Domain\Repositories\AssignmentRepository;

class CompleteAssignmentUseCase
{
    public function __construct(
        private readonly AssignmentRepository $assignmentRepository,
        private readonly GetConsolidatedEvaluatorsUseCase $consolidatedUseCase
    ) {
    }

    public function execute(int $evaluatorId, int $candidateId): void
    {
        $assignment = $this->assignmentRepository->findByEvaluatorAndCandidate($evaluatorId, $candidateId);

        if (!$assignment) {
            throw new AssignmentException("Assignment not found for evaluator {$evaluatorId} and candidate {$candidateId}");
        }

        $previous = $assignment->status()->value();
        $assignment->complete();

        $this->assignmentRepository->update($assignment);
        event(new \Src\Evaluators\Domain\Events\AssignmentStatusChanged(
            $assignment->id() ?? 0,
            $assignment->candidateId(),
            $assignment->evaluatorId(),
            $previous,
            $assignment->status()->value(),
            new \DateTimeImmutable()
        ));
        $this->consolidatedUseCase->invalidateCache();
    }
}
