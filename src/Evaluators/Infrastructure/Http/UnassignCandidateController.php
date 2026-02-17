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

    /**
     * @OA\Delete(
     *     path="/api/evaluators/{evaluatorId}/assignments/{candidateId}",
     *     summary="Unassign a candidate from an evaluator",
     *     tags={"Evaluators"},
     *     @OA\Parameter(
     *         name="evaluatorId",
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
     *         description="Candidate unassigned from evaluator"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Assignment not found"
     *     )
     * )
     */
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
