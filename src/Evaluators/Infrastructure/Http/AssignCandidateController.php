<?php

namespace Src\Evaluators\Infrastructure\Http;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Src\Evaluators\Application\AssignCandidateToEvaluatorUseCase;
use Src\Evaluators\Application\DTO\AssignCandidateRequest;
use Src\Evaluators\Domain\Exceptions\AssignmentException;
use Src\Evaluators\Domain\Exceptions\EvaluatorNotFoundException;

class AssignCandidateController
{
    public function __construct(
        private readonly AssignCandidateToEvaluatorUseCase $useCase
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
            return response()->json([
                'message' => 'Candidate assigned successfully',
                'data' => [
                    'candidate_id' => $dto->candidateId,
                    'evaluator_id' => $dto->evaluatorId
                ]
            ], 200);
        } catch (EvaluatorNotFoundException $e) {
            return response()->json(['error' => $e->getMessage()], 404);
        } catch (AssignmentException $e) {
            return response()->json(['error' => $e->getMessage()], 409);
        }
    }
}
