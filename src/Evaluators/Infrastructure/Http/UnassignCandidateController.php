<?php

namespace Src\Evaluators\Infrastructure\Http;

use Illuminate\Http\JsonResponse;
use Src\Evaluators\Application\UnassignCandidateUseCase;
use Src\Evaluators\Domain\Exceptions\AssignmentException;

class UnassignCandidateController
{
    public function __construct(
        private readonly UnassignCandidateUseCase $useCase
    ) {
    }

    public function __invoke(int $evaluatorId, int $candidateId): JsonResponse
    {
        try {
            $this->useCase->execute($evaluatorId, $candidateId);

            return response()->json([
                'message' => 'Candidate unassigned from evaluator',
            ], 200);
        } catch (AssignmentException $e) {
            return response()->json(['error' => $e->getMessage()], 404);
        }
    }
}

