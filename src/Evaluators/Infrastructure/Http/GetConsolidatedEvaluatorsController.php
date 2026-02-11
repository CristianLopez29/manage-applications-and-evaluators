<?php

namespace Src\Evaluators\Infrastructure\Http;

use Illuminate\Http\JsonResponse;
use Src\Evaluators\Application\GetConsolidatedEvaluatorsUseCase;
use Src\Evaluators\Application\DTO\EvaluatorWithCandidatesDTO;

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
                $val = $request->input('created_from');
                if (is_string($val)) {
                    $createdFrom = new \DateTimeImmutable($val);
                }
            } catch (\Exception $e) {
                $createdFrom = null;
            }
        }
        if ($request->filled('created_to')) {
            try {
                $val = $request->input('created_to');
                if (is_string($val)) {
                    $createdTo = new \DateTimeImmutable($val);
                }
            } catch (\Exception $e) {
                $createdTo = null;
            }
        }

        $search = $request->input('search');
        $specialty = $request->input('specialty');
        $candidateEmailContains = $request->input('candidate_email_contains');
        $sortBy = $request->input('sort_by', 'average_experience');
        $sortDirection = $request->input('sort_direction', 'desc');
        $page = $request->input('page', 1);
        $perPage = $request->input('per_page', 15);
        $minAverageExperience = $request->input('min_average_experience');
        $maxAverageExperience = $request->input('max_average_experience');
        $minTotalAssigned = $request->input('min_total_assigned');
        $maxTotalAssigned = $request->input('max_total_assigned');

        $criteria = new ConsolidatedListCriteria(
            search: is_string($search) ? $search : null,
            sortBy: is_string($sortBy) ? $sortBy : 'average_experience',
            sortDirection: is_string($sortDirection) ? $sortDirection : 'desc',
            page: is_numeric($page) ? (int) $page : 1,
            perPage: is_numeric($perPage) ? (int) $perPage : 15,
            specialtyFilter: is_string($specialty) ? $specialty : null,
            minAverageExperience: is_numeric($minAverageExperience) ? (float) $minAverageExperience : null,
            maxAverageExperience: is_numeric($maxAverageExperience) ? (float) $maxAverageExperience : null,
            minTotalAssigned: is_numeric($minTotalAssigned) ? (int) $minTotalAssigned : null,
            maxTotalAssigned: is_numeric($maxTotalAssigned) ? (int) $maxTotalAssigned : null,
            candidateEmailContains: is_string($candidateEmailContains) ? $candidateEmailContains : null,
            createdFrom: $createdFrom,
            createdTo: $createdTo
        );

        $paginator = $this->useCase->execute($criteria);

        /** @var array<int, EvaluatorWithCandidatesDTO> $items */
        $items = $paginator->items();

        // Transform the paginator items (which are already DTOs) to array
        $data = collect($items)->map(function (EvaluatorWithCandidatesDTO $dto) {
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
