<?php

namespace Src\Candidates\Infrastructure\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Src\Candidates\Application\DTOs\CandidateListItemResponse;
use Src\Candidates\Application\UseCases\ListCandidates;
use Symfony\Component\HttpFoundation\Response;

class ListCandidatesController
{
    public function __construct(
        private readonly ListCandidates $useCase
    ) {
    }

    /**
     * @OA\Get(
     *     path="/api/candidates",
     *     summary="List candidates with filters",
     *     tags={"Candidates"},
     *     @OA\Parameter(
     *         name="status",
     *         in="query",
     *         description="Filter by assignment status (unassigned, pending, in_progress, completed, rejected)",
     *         required=false,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="experience_min",
     *         in="query",
     *         description="Minimum years of experience",
     *         required=false,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="specialty",
     *         in="query",
     *         description="Primary specialty (Backend, Frontend, Fullstack, DevOps, Mobile, QA, Data, Security)",
     *         required=false,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="List of candidates",
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="name", type="string", example="Juan Perez"),
     *                     @OA\Property(property="email", type="string", example="juan@example.com"),
     *                     @OA\Property(property="years_of_experience", type="integer", example=5),
     *                     @OA\Property(property="primary_specialty", type="string", example="Backend"),
     *                     @OA\Property(property="assignment_status", type="string", example="unassigned")
     *                 )
     *             )
     *         )
     *     )
     * )
     *
     * @OA\Get(
     *     path="/api/candidates/search",
     *     summary="Search candidates by email and filters",
     *     tags={"Candidates"},
     *     @OA\Parameter(
     *         name="email",
     *         in="query",
     *         description="Email contains (substring match)",
     *         required=false,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="experience_min",
     *         in="query",
     *         description="Minimum years of experience",
     *         required=false,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="specialty",
     *         in="query",
     *         description="Primary specialty (Backend, Frontend, Fullstack, DevOps, Mobile, QA, Data, Security)",
     *         required=false,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="List of candidates",
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="name", type="string", example="Juan Perez"),
     *                     @OA\Property(property="email", type="string", example="juan@example.com"),
     *                     @OA\Property(property="years_of_experience", type="integer", example=5),
     *                     @OA\Property(property="primary_specialty", type="string", example="Backend"),
     *                     @OA\Property(property="assignment_status", type="string", example="unassigned")
     *                 )
     *             )
     *         )
     *     )
     * )
     */
    public function __invoke(Request $request): JsonResponse
    {
        $status = $request->query('status');
        $minExperience = $request->query('experience_min') ? (int)$request->query('experience_min') : null;
        $emailContains = $request->query('email');
        $primarySpecialty = $request->query('specialty');

        $result = $this->useCase->execute(
            $status,
            $minExperience,
            $emailContains,
            $primarySpecialty
        );

        return new JsonResponse(['data' => array_values($result)], Response::HTTP_OK);
    }
}
