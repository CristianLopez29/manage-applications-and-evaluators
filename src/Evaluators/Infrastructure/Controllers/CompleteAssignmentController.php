<?php

namespace Src\Evaluators\Infrastructure\Controllers;

use Illuminate\Http\JsonResponse;
use Src\Evaluators\Application\UseCases\CompleteAssignment;
use Src\Evaluators\Domain\Exceptions\AssignmentException;
use Symfony\Component\HttpFoundation\Response;

class CompleteAssignmentController
{
    public function __construct(
        private readonly CompleteAssignment $useCase
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

            return new JsonResponse([
                'message' => 'Assignment completed',
            ], Response::HTTP_OK);
        } catch (AssignmentException $e) {
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_NOT_FOUND);
        }
    }
}
