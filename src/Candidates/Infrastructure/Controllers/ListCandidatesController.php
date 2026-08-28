<?php

namespace Src\Candidates\Infrastructure\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Src\Candidates\Application\DTOs\CandidateListItemResponse;
use Src\Candidates\Application\UseCases\ListCandidates;
use Src\Evaluators\Domain\Enums\AssignmentStatus;
use Symfony\Component\HttpFoundation\Response;

class ListCandidatesController
{
    /**
     * "unassigned" is not an AssignmentStatus: it means the candidate has no
     * active assignment at all, which the use case resolves separately.
     */
    private const UNASSIGNED_FILTER = 'unassigned';

    public function __construct(
        private readonly ListCandidates $useCase
    ) {
    }

    /**
     * @OA\Get(
     *     path="/api/v1/candidates",
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
     *     path="/api/v1/candidates/search",
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
        $filters = $request->validate([
            'status' => ['nullable', 'string', Rule::in($this->allowedStatusFilters())],
            'experience_min' => ['nullable', 'integer', 'min:0', 'max:100'],
            'email' => ['nullable', 'string', 'max:255'],
            'specialty' => ['nullable', 'string', 'max:100'],
        ]);

        $minExperience = $filters['experience_min'] ?? null;

        $result = $this->useCase->execute(
            $this->stringOrNull($filters, 'status'),
            is_numeric($minExperience) ? (int) $minExperience : null,
            $this->stringOrNull($filters, 'email'),
            $this->stringOrNull($filters, 'specialty')
        );

        return new JsonResponse(['data' => array_values($result)], Response::HTTP_OK);
    }

    /**
     * @return list<string>
     */
    private function allowedStatusFilters(): array
    {
        return [
            self::UNASSIGNED_FILTER,
            ...array_column(AssignmentStatus::cases(), 'value'),
        ];
    }

    /**
     * @param array<string, mixed> $filters
     */
    private function stringOrNull(array $filters, string $key): ?string
    {
        $value = $filters[$key] ?? null;

        return is_string($value) && $value !== '' ? $value : null;
    }
}
