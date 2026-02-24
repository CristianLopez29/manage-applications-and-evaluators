<?php

namespace Src\Evaluators\Infrastructure\Controllers;

use Illuminate\Http\JsonResponse;
use Src\Evaluators\Application\UseCases\UnassignCandidate;
use Src\Evaluators\Domain\Exceptions\AssignmentException;
use Symfony\Component\HttpFoundation\Response;

class UnassignCandidateController
{
    public function __construct(
        private readonly UnassignCandidate $useCase
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

            return new JsonResponse([
                'message' => 'Candidate unassigned from evaluator',
            ], Response::HTTP_OK);
        } catch (AssignmentException $e) {
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_NOT_FOUND);
        }
    }
}
