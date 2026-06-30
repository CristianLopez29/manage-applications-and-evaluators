<?php

declare(strict_types=1);

namespace Src\Evaluators\Infrastructure\Controllers;

use Illuminate\Http\JsonResponse;
use Src\Evaluators\Application\UseCases\GetCandidateAssignmentHistory;
use Symfony\Component\HttpFoundation\Response;

class GetCandidateAssignmentHistoryController
{
    public function __construct(
        private readonly GetCandidateAssignmentHistory $useCase
    ) {
    }

    /**
     * @OA\Get(
     *     path="/api/v1/candidates/{candidateId}/assignment-history",
     *     summary="Chronological status timeline of a candidate's assignments",
     *     tags={"Evaluators"},
     *     @OA\Parameter(
     *         name="candidateId",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Assignment history returned"
     *     )
     * )
     */
    public function __invoke(int $candidateId): JsonResponse
    {
        return new JsonResponse([
            'data' => $this->useCase->execute($candidateId),
        ], Response::HTTP_OK);
    }
}
