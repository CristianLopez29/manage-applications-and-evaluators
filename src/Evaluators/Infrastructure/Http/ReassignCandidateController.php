<?php

namespace Src\Evaluators\Infrastructure\Http;

use Illuminate\Http\JsonResponse;
use Src\Evaluators\Application\ReassignCandidateUseCase;
use Src\Evaluators\Domain\Exceptions\AssignmentException;
use Src\Evaluators\Domain\Exceptions\EvaluatorNotFoundException;

class ReassignCandidateController
{
    public function __construct(
        private readonly ReassignCandidateUseCase $useCase
    ) {
    }

    public function __invoke(int $newEvaluatorId, int $candidateId): JsonResponse
    {
        try {
            $this->useCase->execute($newEvaluatorId, $candidateId);

            return response()->json([
                'message' => 'Candidate reassigned to new evaluator',
                'data' => [
                    'candidate_id' => $candidateId,
                    'evaluator_id' => $newEvaluatorId,
                ],
            ], 200);
        } catch (AssignmentException|EvaluatorNotFoundException $e) {
            return response()->json(['error' => $e->getMessage()], 404);
        }
    }
}

