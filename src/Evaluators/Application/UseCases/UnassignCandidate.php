<?php

namespace Src\Evaluators\Application\UseCases;

use Src\Evaluators\Domain\Exceptions\AssignmentException;
use Src\Evaluators\Domain\Repositories\AssignmentRepository;

class UnassignCandidate
{
    public function __construct(
        private readonly AssignmentRepository $assignmentRepository,
        private readonly GetConsolidatedEvaluators $consolidatedUseCase
    ) {
    }

    public function execute(int $evaluatorId, int $candidateId): void
    {
        $assignment = $this->assignmentRepository->findByEvaluatorAndCandidate($evaluatorId, $candidateId);

        if (!$assignment) {
            throw new AssignmentException("Assignment not found for evaluator {$evaluatorId} and candidate {$candidateId}");
        }

        $this->assignmentRepository->deleteByEvaluatorAndCandidate($evaluatorId, $candidateId);
        $this->consolidatedUseCase->invalidateCache();
    }
}

