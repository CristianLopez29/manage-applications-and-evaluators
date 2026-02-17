<?php

namespace Src\Evaluators\Infrastructure\Http;

use Illuminate\Http\JsonResponse;
use Src\Evaluators\Application\RejectAssignmentUseCase;
use Src\Evaluators\Domain\Exceptions\AssignmentException;

class RejectAssignmentController
{
    public function __construct(
        private readonly RejectAssignmentUseCase $useCase
    ) {
    }

    /**
     * @OA\Put(
     *     path="/api/evaluators/{evaluatorId}/assignments/{candidateId}/reject",
     *     summary="Reject an assignment",
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
     *         description="Assignment rejected"
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
                'message' => 'Assignment rejected',
            ], 200);
        } catch (AssignmentException $e) {
            return response()->json(['error' => $e->getMessage()], 404);
        }
    }
}
