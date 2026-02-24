<?php

namespace Src\Evaluators\Infrastructure\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Src\Evaluators\Application\UseCases\AssignCandidateToEvaluator;
use Src\Evaluators\Application\DTOs\AssignCandidateRequest;
use Src\Evaluators\Application\DTOs\AssignmentResponse;
use Src\Evaluators\Domain\Exceptions\AssignmentException;
use Src\Evaluators\Domain\Exceptions\EvaluatorNotFoundException;
use Symfony\Component\HttpFoundation\Response;

class AssignCandidateController
{
    public function __construct(
        private readonly AssignCandidateToEvaluator $useCase
    ) {
    }

    /**
     * @OA\Post(
     *     path="/api/evaluators/{evaluatorId}/assign-candidate",
     *     summary="Assign a candidate to an evaluator",
     *     tags={"Evaluators"},
     *     @OA\Parameter(
     *         name="evaluatorId",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"candidate_id"},
     *             @OA\Property(property="candidate_id", type="integer", example=1)
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Candidate assigned successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Candidate assigned successfully")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Evaluator or Candidate not found"
     *     ),
     *     @OA\Response(
     *         response=409,
     *         description="Candidate already assigned"
     *     )
     * )
     */
    public function __invoke(int $evaluatorId, Request $request): JsonResponse
    {
        // 1. Input validation
        $validated = $request->validate([
            'candidate_id' => 'required|integer|exists:candidates,id',
        ]);

        try {
            // 2. Map to DTO
            $dto = new AssignCandidateRequest(
                candidateId: $validated['candidate_id'],
                evaluatorId: $evaluatorId
            );

            // 3. Execute Use Case
            $this->useCase->execute($dto);

            // 4. HTTP Response
            $responseDto = new AssignmentResponse(
                $dto->candidateId,
                $dto->evaluatorId
            );

            return new JsonResponse([
                'message' => 'Candidate assigned successfully',
                'data' => $responseDto
            ], Response::HTTP_OK);
        } catch (EvaluatorNotFoundException $e) {
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_NOT_FOUND);
        } catch (AssignmentException $e) {
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_CONFLICT);
        }
    }
}
