<?php

namespace Src\Evaluators\Application;

use Src\Candidates\Domain\Repositories\CandidateRepository;
use Src\Evaluators\Domain\Exceptions\EvaluatorNotFoundException;
use Src\Evaluators\Domain\Repositories\AssignmentRepository;
use Src\Evaluators\Domain\Repositories\EvaluatorRepository;

class GetEvaluatorCandidatesUseCase
{
    public function __construct(
        private readonly EvaluatorRepository $evaluatorRepository,
        private readonly AssignmentRepository $assignmentRepository,
        private readonly CandidateRepository $candidateRepository
    ) {
    }

    /**
     * @return array<int, array{candidate: \Src\Candidates\Domain\Candidate, assignment: \Src\Evaluators\Domain\CandidateAssignment}>
     */
    public function execute(int $evaluatorId): array
    {
        // 1. Verify that the evaluator exists
        $evaluator = $this->evaluatorRepository->findById($evaluatorId);
        if (!$evaluator) {
            throw EvaluatorNotFoundException::withId($evaluatorId);
        }

        // 2. Get all assignments for the evaluator
        $assignments = $this->assignmentRepository->findByEvaluatorId($evaluatorId);

        // 3. Get candidates with their assignment information
        $result = [];
        foreach ($assignments as $assignment) {
            $candidate = $this->candidateRepository->findById($assignment->candidateId());

            if ($candidate) {
                $result[] = [
                    'candidate' => $candidate,
                    'assignment' => $assignment
                ];
            }
        }

        return $result;
    }
}
