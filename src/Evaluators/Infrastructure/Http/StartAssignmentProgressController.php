<?php

namespace Src\Evaluators\Infrastructure\Http;

use Illuminate\Http\JsonResponse;
use Src\Evaluators\Application\StartAssignmentProgressUseCase;
use Src\Evaluators\Domain\Exceptions\AssignmentException;

class StartAssignmentProgressController
{
    public function __construct(
        private readonly StartAssignmentProgressUseCase $useCase
    ) {
    }

    /**
     * @OA\Put(
     *     path="/api/evaluators/{evaluatorId}/assignments/{candidateId}/start-progress",
     *     summary="Move assignment to in_progress",
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
     *         description="Assignment moved to in_progress"
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
                'message' => 'Assignment moved to in_progress',
            ], 200);
        } catch (AssignmentException $e) {
            return response()->json(['error' => $e->getMessage()], 404);
        }
    }
}
