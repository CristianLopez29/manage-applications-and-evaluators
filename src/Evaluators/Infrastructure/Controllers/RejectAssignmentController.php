<?php

namespace Src\Evaluators\Infrastructure\Controllers;

use Illuminate\Http\JsonResponse;
use Src\Evaluators\Application\UseCases\RejectAssignment;
use Src\Evaluators\Domain\Exceptions\AssignmentException;
use Symfony\Component\HttpFoundation\Response;

class RejectAssignmentController
{
    public function __construct(
        private readonly RejectAssignment $useCase
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

            return new JsonResponse([
                'message' => 'Assignment rejected',
            ], Response::HTTP_OK);
        } catch (AssignmentException $e) {
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_NOT_FOUND);
        }
    }
}
