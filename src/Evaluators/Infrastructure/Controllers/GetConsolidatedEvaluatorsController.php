<?php

namespace Src\Evaluators\Infrastructure\Controllers;

use Illuminate\Http\JsonResponse;
use Src\Evaluators\Application\DTOs\EvaluatorListItemResponse;
use Src\Evaluators\Application\UseCases\GetConsolidatedEvaluators;
use Src\Evaluators\Application\DTOs\EvaluatorWithCandidatesDTO;

use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Src\Evaluators\Domain\Criteria\ConsolidatedListCriteria;
use Symfony\Component\HttpFoundation\Response;

class GetConsolidatedEvaluatorsController
{
    private const DEFAULT_PER_PAGE = 15;

    /**
     * An uncapped per_page let a single request scan the whole join and, since
     * per_page is part of the cache key, mint unbounded distinct cache entries.
     */
    private const MAX_PER_PAGE = 100;

    public function __construct(
        private readonly GetConsolidatedEvaluators $useCase
    ) {
    }

    /**
     * @OA\Get(
     *     path="/api/v1/evaluators/consolidated",
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
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
            'specialty' => ['nullable', 'string', 'max:100'],
            'candidate_email_contains' => ['nullable', 'string', 'max:255'],
            'sort_by' => ['nullable', 'string', 'max:60'],
            'sort_direction' => ['nullable', 'string', Rule::in(['asc', 'desc'])],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:' . self::MAX_PER_PAGE],
            'min_average_experience' => ['nullable', 'numeric'],
            'max_average_experience' => ['nullable', 'numeric'],
            'min_total_assigned' => ['nullable', 'integer', 'min:0'],
            'max_total_assigned' => ['nullable', 'integer', 'min:0'],
            'created_from' => ['nullable', 'date'],
            'created_to' => ['nullable', 'date'],
        ]);

        $criteria = new ConsolidatedListCriteria(
            search: $this->stringOrNull($filters, 'search'),
            sortBy: $this->stringOrNull($filters, 'sort_by') ?? 'average_experience',
            sortDirection: $this->stringOrNull($filters, 'sort_direction') ?? 'desc',
            page: $this->intOr($filters, 'page', 1),
            perPage: $this->intOr($filters, 'per_page', self::DEFAULT_PER_PAGE),
            specialtyFilter: $this->stringOrNull($filters, 'specialty'),
            minAverageExperience: $this->floatOrNull($filters, 'min_average_experience'),
            maxAverageExperience: $this->floatOrNull($filters, 'max_average_experience'),
            minTotalAssigned: $this->intOrNull($filters, 'min_total_assigned'),
            maxTotalAssigned: $this->intOrNull($filters, 'max_total_assigned'),
            candidateEmailContains: $this->stringOrNull($filters, 'candidate_email_contains'),
            createdFrom: $this->dateOrNull($filters, 'created_from'),
            createdTo: $this->dateOrNull($filters, 'created_to')
        );

        $paginator = $this->useCase->execute($criteria);

        return new JsonResponse([
            'data' => $paginator->items(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ]
        ], Response::HTTP_OK);
    }

    /**
     * @param array<string, mixed> $filters
     */
    private function stringOrNull(array $filters, string $key): ?string
    {
        $value = $filters[$key] ?? null;

        return is_string($value) && $value !== '' ? $value : null;
    }

    /**
     * @param array<string, mixed> $filters
     */
    private function intOrNull(array $filters, string $key): ?int
    {
        $value = $filters[$key] ?? null;

        return is_numeric($value) ? (int) $value : null;
    }

    /**
     * @param array<string, mixed> $filters
     */
    private function intOr(array $filters, string $key, int $fallback): int
    {
        return $this->intOrNull($filters, $key) ?? $fallback;
    }

    /**
     * @param array<string, mixed> $filters
     */
    private function floatOrNull(array $filters, string $key): ?float
    {
        $value = $filters[$key] ?? null;

        return is_numeric($value) ? (float) $value : null;
    }

    /**
     * @param array<string, mixed> $filters
     */
    private function dateOrNull(array $filters, string $key): ?\DateTimeImmutable
    {
        $value = $this->stringOrNull($filters, $key);

        return $value === null ? null : new \DateTimeImmutable($value);
    }
}
