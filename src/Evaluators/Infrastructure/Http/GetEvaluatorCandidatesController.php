<?php

namespace Src\Evaluators\Infrastructure\Http;

use Illuminate\Http\JsonResponse;
use Src\Evaluators\Application\GetEvaluatorCandidatesUseCase;
use Src\Evaluators\Domain\Exceptions\EvaluatorNotFoundException;

class GetEvaluatorCandidatesController
{
    public function __construct(
        private readonly GetEvaluatorCandidatesUseCase $useCase
    ) {
    }

    /**
     * @OA\Get(
     *     path="/api/evaluators/{evaluatorId}/candidates",
     *     summary="Get candidates assigned to an evaluator",
     *     tags={"Evaluators"},
     *     @OA\Parameter(
     *         name="evaluatorId",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="List of assigned candidates",
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="name", type="string", example="Juan Perez"),
     *                     @OA\Property(property="email", type="string", example="juan@example.com"),
     *                     @OA\Property(property="years_of_experience", type="integer", example=5),
     *                     @OA\Property(property="cv", type="string", example="CV Content"),
     *                     @OA\Property(property="status", type="string", example="Pending")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Evaluator not found"
     *     )
     * )
     */
    public function __invoke(int $evaluatorId): JsonResponse
    {
        try {
            // 1. Execute the Use Case
            $result = $this->useCase->execute($evaluatorId);

            // 2. Map the response
            $candidates = array_map(function ($item) {
                $candidate = $item['candidate'];
                $assignment = $item['assignment'];

                return [
                    'id' => $candidate->id(),
                    'name' => $candidate->name(),
                    'email' => $candidate->email()->value(),
                    'years_of_experience' => $candidate->yearsOfExperience()->value(),
                    'assigned_at' => $assignment->assignedAt()->format('Y-m-d H:i:s'),
                    'status' => $assignment->status()->value()
                ];
            }, $result);

            // 3. Return the HTTP response
            return response()->json([
                'data' => $candidates,
                'meta' => [
                    'total' => count($candidates),
                    'evaluator_id' => $evaluatorId
                ]
            ], 200);
        } catch (EvaluatorNotFoundException $e) {
            return response()->json(['error' => $e->getMessage()], 404);
        }
    }
}
