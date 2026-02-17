<?php

namespace Src\Evaluators\Infrastructure\Http;

use Illuminate\Http\JsonResponse;
use Src\Evaluators\Application\CompleteAssignmentUseCase;
use Src\Evaluators\Domain\Exceptions\AssignmentException;

class CompleteAssignmentController
{
    public function __construct(
        private readonly CompleteAssignmentUseCase $useCase
    ) {
    }

    /**
     * @OA\Put(
     *     path="/api/evaluators/{evaluatorId}/assignments/{candidateId}/complete",
     *     summary="Mark assignment as completed",
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
     *         description="Assignment completed"
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
                'message' => 'Assignment completed',
            ], 200);
        } catch (AssignmentException $e) {
            return response()->json(['error' => $e->getMessage()], 404);
        }
    }
}
