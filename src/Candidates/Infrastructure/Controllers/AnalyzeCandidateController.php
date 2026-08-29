<?php

namespace Src\Candidates\Infrastructure\Controllers;

use Illuminate\Http\JsonResponse;
use Src\Candidates\Application\UseCases\RequestCandidateAnalysis;
use Src\Candidates\Domain\Exceptions\AiUsageBudgetExceededException;
use Symfony\Component\HttpFoundation\Response;

class AnalyzeCandidateController
{
    public function __construct(
        private readonly RequestCandidateAnalysis $useCase
    ) {
    }

    /**
     * @OA\Post(
     *     path="/api/v1/candidates/{id}/analyze",
     *     summary="Queue AI analysis for candidate CV",
     *     tags={"Candidates"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=202,
     *         description="Analysis queued"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Candidate not found"
     *     ),
     *     @OA\Response(
     *         response=429,
     *         description="Daily AI analysis budget reached"
     *     )
     * )
     */
    public function __invoke(int $id): JsonResponse
    {
        try {
            $this->useCase->execute($id);
        } catch (AiUsageBudgetExceededException $e) {
            return new JsonResponse(['message' => $e->getMessage()], Response::HTTP_TOO_MANY_REQUESTS);
        }

        return new JsonResponse([
            'status' => 'processing',
            'message' => 'Analysis queued',
        ], Response::HTTP_ACCEPTED);
    }
}
