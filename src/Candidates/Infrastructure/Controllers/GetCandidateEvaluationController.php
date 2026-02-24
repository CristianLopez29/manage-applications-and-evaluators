<?php

namespace Src\Candidates\Infrastructure\Controllers;

use Illuminate\Http\JsonResponse;
use Src\Candidates\Application\UseCases\GetCandidateEvaluation;
use Symfony\Component\HttpFoundation\Response;

class GetCandidateEvaluationController
{
    public function __construct(
        private readonly GetCandidateEvaluation $useCase
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
            return new JsonResponse([
                'status' => 'processing',
                'message' => 'Evaluation not ready yet',
            ], Response::HTTP_ACCEPTED);
        }

        return new JsonResponse([
            'data' => $result,
        ], Response::HTTP_OK);
    }
}
