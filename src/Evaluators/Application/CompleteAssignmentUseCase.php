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

        $assignment->complete();

        $this->assignmentRepository->update($assignment);
        $this->consolidatedUseCase->invalidateCache();
    }
}

