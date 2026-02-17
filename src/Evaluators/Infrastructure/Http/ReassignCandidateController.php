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

    /**
     * @OA\Put(
     *     path="/api/evaluators/{newEvaluatorId}/reassign-candidate/{candidateId}",
     *     summary="Reassign candidate to a new evaluator",
     *     tags={"Evaluators"},
     *     @OA\Parameter(
     *         name="newEvaluatorId",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="candidateId",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Candidate reassigned to new evaluator"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Evaluator or Assignment not found"
     *     )
     * )
     */
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
