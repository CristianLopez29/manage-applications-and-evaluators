<?php

namespace Src\Evaluators\Infrastructure\Controllers;

use Illuminate\Http\JsonResponse;
use Src\Evaluators\Application\DTOs\AssignmentResponse;
use Src\Evaluators\Application\UseCases\ReassignCandidate;
use Src\Evaluators\Domain\Exceptions\AssignmentException;
use Src\Evaluators\Domain\Exceptions\EvaluatorNotFoundException;
use Symfony\Component\HttpFoundation\Response;

class ReassignCandidateController
{
    public function __construct(
        private readonly ReassignCandidate $useCase
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

            $responseDto = new AssignmentResponse(
                $candidateId,
                $newEvaluatorId
            );

            return new JsonResponse([
                'message' => 'Candidate reassigned to new evaluator',
                'data' => $responseDto
            ], Response::HTTP_OK);
        } catch (AssignmentException|EvaluatorNotFoundException $e) {
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_NOT_FOUND);
        }
    }
}
