<?php

namespace Src\Candidates\Infrastructure\Http;

use Illuminate\Http\JsonResponse;
use Src\Candidates\Application\GetCandidateEvaluationUseCase;

class GetCandidateEvaluationController
{
    public function __construct(
        private readonly GetCandidateEvaluationUseCase $useCase
    ) {
    }

    /**
     * @OA\Get(
     *     path="/api/candidates/{id}/evaluation",
     *     summary="Get latest AI evaluation for a candidate",
     *     tags={"Candidates"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Evaluation found"
     *     ),
     *     @OA\Response(
     *         response=202,
     *         description="Evaluation pending"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Candidate not found"
     *     )
     * )
     */
    public function __invoke(int $id): JsonResponse
    {
        $result = $this->useCase->execute($id);
        if ($result === null) {
            return response()->json([
                'status' => 'processing',
                'message' => 'Evaluation not ready yet',
            ], 202);
        }

        return response()->json([
            'data' => $result,
        ]);
    }
}

