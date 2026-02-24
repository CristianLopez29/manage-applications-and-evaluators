<?php

namespace Src\Evaluators\Infrastructure\Controllers;

use Illuminate\Http\JsonResponse;
use Src\Evaluators\Application\DTOs\EvaluatorCandidateResponse;
use Src\Evaluators\Application\UseCases\GetEvaluatorCandidates;
use Src\Evaluators\Domain\Exceptions\EvaluatorNotFoundException;
use Symfony\Component\HttpFoundation\Response;

class GetEvaluatorCandidatesController
{
    public function __construct(
        private readonly GetEvaluatorCandidates $useCase
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
            $candidates = $this->useCase->execute($evaluatorId);

            // 2. Return the HTTP response
            return new JsonResponse([
                'data' => $candidates,
                'meta' => [
                    'total' => count($candidates),
                    'evaluator_id' => $evaluatorId
                ]
            ], Response::HTTP_OK);
        } catch (EvaluatorNotFoundException $e) {
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_NOT_FOUND);
        }
    }
}
