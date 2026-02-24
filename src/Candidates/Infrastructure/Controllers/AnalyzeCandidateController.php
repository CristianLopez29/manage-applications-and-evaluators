<?php

namespace Src\Candidates\Infrastructure\Controllers;

use Illuminate\Http\JsonResponse;
use Src\Candidates\Application\UseCases\RequestCandidateAnalysis;
use Symfony\Component\HttpFoundation\Response;

class AnalyzeCandidateController
{
    public function __construct(
        private readonly RequestCandidateAnalysis $useCase
    ) {
    }

    /**
     * @OA\Post(
     *     path="/api/candidates/{id}/analyze",
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
     *     )
     * )
     */
    public function __invoke(int $id): JsonResponse
    {
        $this->useCase->execute($id);

        return new JsonResponse([
            'status' => 'processing',
            'message' => 'Analysis queued',
        ], Response::HTTP_ACCEPTED);
    }
}
