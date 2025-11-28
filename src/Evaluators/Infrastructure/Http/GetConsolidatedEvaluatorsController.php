<?php

namespace Src\Evaluators\Infrastructure\Http;

use Illuminate\Http\JsonResponse;
use Src\Evaluators\Application\GetConsolidatedEvaluatorsUseCase;

use Illuminate\Http\Request;
use Src\Evaluators\Domain\Criteria\ConsolidatedListCriteria;

class GetConsolidatedEvaluatorsController
{
    public function __construct(
        private readonly GetConsolidatedEvaluatorsUseCase $useCase
    ) {
    }

    /**
     * @OA\Get(
     *     path="/api/evaluators/consolidated",
     *     summary="Get consolidated list of evaluators and their candidates",
     *     tags={"Evaluators"},
     *     @OA\Parameter(
     *         name="page",
     *         in="query",
     *         description="Page number",
     *         required=false,
     *         @OA\Schema(type="integer", default=1)
     *     ),
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         description="Items per page",
     *         required=false,
     *         @OA\Schema(type="integer", default=15)
     *     ),
     *     @OA\Parameter(
     *         name="search",
     *         in="query",
     *         description="Search by name or email",
     *         required=false,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="sort_by",
     *         in="query",
     *         description="Sort by field (name, email, created_at, average_experience, specialty, total_assigned_candidates, concatenated_candidate_emails)",
     *         required=false,
     *         @OA\Schema(type="string", default="average_experience")
     *     ),
     *     @OA\Parameter(
     *         name="sort_direction",
     *         in="query",
     *         description="Sort direction (asc, desc)",
     *         required=false,
     *         @OA\Schema(type="string", default="desc")
     *     ),
     *     @OA\Parameter(
     *         name="specialty",
     *         in="query",
     *         description="Filter by evaluator specialty (e.g., Backend, Frontend, Fullstack, DevOps, Mobile, QA, Data, Security)",
     *         required=false,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="min_average_experience",
     *         in="query",
     *         description="Minimum average candidate experience (years)",
     *         required=false,
     *         @OA\Schema(type="number", format="float")
     *     ),
     *     @OA\Parameter(
     *         name="max_average_experience",
     *         in="query",
     *         description="Maximum average candidate experience (years)",
     *         required=false,
     *         @OA\Schema(type="number", format="float")
     *     ),
     *     @OA\Parameter(
     *         name="min_total_assigned",
     *         in="query",
     *         description="Minimum total assigned candidates",
     *         required=false,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="max_total_assigned",
     *         in="query",
     *         description="Maximum total assigned candidates",
     *         required=false,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="candidate_email_contains",
     *         in="query",
     *         description="Substring to match within concatenated candidate emails",
     *         required=false,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="created_from",
     *         in="query",
     *         description="Filter evaluators created at or after this datetime",
     *         required=false,
     *         @OA\Schema(type="string", format="date-time", example="2025-11-01 00:00:00")
     *     ),
     *     @OA\Parameter(
     *         name="created_to",
     *         in="query",
     *         description="Filter evaluators created at or before this datetime",
     *         required=false,
     *         @OA\Schema(type="string", format="date-time", example="2025-11-15 23:59:59")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Consolidated list retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="name", type="string", example="Maria Gonzalez"),
     *                     @OA\Property(property="email", type="string", example="maria@example.com"),
     *                     @OA\Property(property="specialty", type="string", example="Backend"),
     *                     @OA\Property(property="average_candidate_experience", type="number", format="float", example=4.5),
     *                     @OA\Property(property="total_assigned_candidates", type="integer", example=3),
     *                     @OA\Property(property="concatenated_candidate_emails", type="string", example="alpha@example.com, bravo@example.com"),
     *                     @OA\Property(
     *                         property="candidates",
     *                         type="array",
     *                         @OA\Items(
     *                             @OA\Property(property="id", type="integer", example=1),
     *                             @OA\Property(property="name", type="string", example="Juan Perez"),
     *                             @OA\Property(property="email", type="string", example="juan@example.com"),
     *                             @OA\Property(property="years_of_experience", type="integer", example=5)
     *                             ,@OA\Property(property="assigned_at", type="string", format="date-time", example="2025-11-15 12:34:56")
     *                         )
     *                     )
     *                 )
     *             ),
     *             @OA\Property(
     *                 property="meta",
     *                 type="object",
     *                 @OA\Property(property="current_page", type="integer", example=1),
     *                 @OA\Property(property="last_page", type="integer", example=5),
     *                 @OA\Property(property="per_page", type="integer", example=15),
     *                 @OA\Property(property="total", type="integer", example=75)
     *             )
     *         )
     *     )
     * )
     */
    public function __invoke(Request $request): JsonResponse
    {
        $createdFrom = null;
        $createdTo = null;
        if ($request->filled('created_from')) {
            try {
                $createdFrom = new \DateTimeImmutable($request->input('created_from'));
            } catch (\Exception $e) {
                $createdFrom = null;
            }
        }
        if ($request->filled('created_to')) {
            try {
                $createdTo = new \DateTimeImmutable($request->input('created_to'));
            } catch (\Exception $e) {
                $createdTo = null;
            }
        }

        $criteria = new ConsolidatedListCriteria(
            search: $request->input('search'),
            sortBy: $request->input('sort_by', 'average_experience'),
            sortDirection: $request->input('sort_direction', 'desc'),
            page: (int) $request->input('page', 1),
            perPage: (int) $request->input('per_page', 15),
            specialtyFilter: $request->input('specialty'),
            minAverageExperience: ($request->input('min_average_experience') !== null) ? (float) $request->input('min_average_experience') : null,
            maxAverageExperience: ($request->input('max_average_experience') !== null) ? (float) $request->input('max_average_experience') : null,
            minTotalAssigned: ($request->input('min_total_assigned') !== null) ? (int) $request->input('min_total_assigned') : null,
            maxTotalAssigned: ($request->input('max_total_assigned') !== null) ? (int) $request->input('max_total_assigned') : null,
            candidateEmailContains: $request->input('candidate_email_contains'),
            createdFrom: $createdFrom,
            createdTo: $createdTo
        );

        $paginator = $this->useCase->execute($criteria);

        // Transform the paginator items (which are already DTOs) to array
        $data = collect($paginator->items())->map(function ($dto) {
            return [
                'id' => $dto->evaluator->id(),
                'name' => $dto->evaluator->name()->value(),
                'email' => $dto->evaluator->email()->value(),
                'specialty' => $dto->evaluator->specialty()->value(),
                'average_candidate_experience' => round($dto->averageExperience, 1),
                'total_assigned_candidates' => count($dto->candidates), // COUNT desde SQL
                'concatenated_candidate_emails' => $dto->concatenatedEmails, // GROUP_CONCAT desde SQL
                'candidates' => array_map(function ($candidate) use ($dto) {
                    return [
                        'id' => $candidate->id(),
                        'name' => $candidate->name(),
                        'email' => $candidate->email()->value(),
                        'years_of_experience' => $candidate->yearsOfExperience()->value(),
                        'assigned_at' => $dto->assignmentsByCandidateId[$candidate->id()] ?? null,
                    ];
                }, $dto->candidates)
            ];
        });

        return response()->json([
            'data' => $data,
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ]
        ], 200);
    }
}
