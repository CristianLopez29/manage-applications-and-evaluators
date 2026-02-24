<?php

namespace Src\Candidates\Infrastructure\Controllers;

use Illuminate\Http\JsonResponse;
use Src\Candidates\Application\DTOs\CandidateSummaryResponse;
use Src\Candidates\Application\UseCases\GetCandidateSummary;
use Symfony\Component\HttpFoundation\Response;

class GetCandidateSummaryController
{
    public function __construct(
        private readonly GetCandidateSummary $useCase
    ) {
    }

    /**
     * @OA\Get(
     *     path="/api/candidates/{id}/summary",
     *     summary="Get candidate summary",
     *     tags={"Candidates"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Candidate summary retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="id", type="integer", example=1),
     *             @OA\Property(property="name", type="string", example="Juan Perez"),
     *             @OA\Property(property="email", type="string", example="juan@example.com"),
     *             @OA\Property(property="years_of_experience", type="integer", example=5),
     *             @OA\Property(property="cv_preview", type="string", example="CV Content..."),
     *             @OA\Property(property="cv_pdf", type="boolean", example=true),
     *             @OA\Property(property="cv_download_url", type="string", nullable=true, example="http://localhost/api/candidates/1/cv"),
     *             @OA\Property(property="created_at", type="string", format="date-time"),
     *             @OA\Property(
     *                 property="assignment",
     *                 type="object",
     *                 nullable=true,
     *                 @OA\Property(property="evaluator_name", type="string", example="Maria Gonzalez"),
     *                 @OA\Property(property="status", type="string", example="Pending"),
     *                 @OA\Property(property="assigned_at", type="string", format="date-time")
     *             ),
     *             @OA\Property(
     *                 property="validations",
     *                 type="object",
     *                 @OA\Property(property="cv_required", type="boolean", example=true),
     *                 @OA\Property(property="valid_email", type="boolean", example=true),
     *                 @OA\Property(property="minimum_experience", type="boolean", example=true)
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Candidate not found"
     *     )
     * )
     */
    public function __invoke(int $id): JsonResponse
    {
        try {
            $dto = $this->useCase->execute($id);

            $response = new CandidateSummaryResponse(
                candidateInfo: [
                    'id' => $dto->id,
                    'name' => $dto->name,
                    'email' => $dto->email,
                    'experience_years' => $dto->yearsOfExperience,
                    'cv_preview' => substr($dto->cvContent, 0, 100) . '...',
                    'cv_pdf' => $dto->hasPdf,
                    'cv_download_url' => $dto->hasPdf ? url("/api/candidates/{$dto->id}/cv") : null,
                ],
                assignmentInfo: $dto->assignment ?? 'Unassigned',
                complianceReport: $dto->validationResults
            );

            return new JsonResponse([
                'data' => $response
            ], Response::HTTP_OK);

        } catch (\RuntimeException $e) {
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_NOT_FOUND);
        }
    }
}
