<?php

namespace Src\Evaluators\Application\UseCases;

use Src\Candidates\Domain\Repositories\CandidateRepository;
use Src\Evaluators\Application\DTOs\EvaluatorCandidateResponse;
use Src\Evaluators\Application\Transformers\EvaluatorCandidateTransformer;
use Src\Evaluators\Domain\Exceptions\EvaluatorNotFoundException;
use Src\Evaluators\Domain\Repositories\AssignmentRepository;
use Src\Evaluators\Domain\Repositories\EvaluatorRepository;

class GetEvaluatorCandidates
{
    public function __construct(
        private readonly EvaluatorRepository $evaluatorRepository,
        private readonly AssignmentRepository $assignmentRepository,
        private readonly CandidateRepository $candidateRepository,
        private readonly EvaluatorCandidateTransformer $transformer
    ) {
    }

    /**
     * @return array<int, EvaluatorCandidateResponse>
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
                $result[] = $this->transformer->transform($candidate, $assignment);
            }
        }

        return $result;
    }
}
