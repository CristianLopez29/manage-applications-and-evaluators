<?php

namespace Src\Evaluators\Application;

use Src\Evaluators\Domain\Repositories\EvaluatorRepository;
use Src\Evaluators\Domain\Criteria\ConsolidatedListCriteria;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;

use Src\Evaluators\Application\DTO\EvaluatorWithCandidatesDTO;

class GetConsolidatedEvaluatorsUseCase
{
    private const CACHE_TTL = 300; // 5 minutos

    public function __construct(
        private readonly EvaluatorRepository $repository
    ) {
    }

    /**
     * @return LengthAwarePaginator<int, EvaluatorWithCandidatesDTO>
     */
    public function execute(ConsolidatedListCriteria $criteria): LengthAwarePaginator
    {
        // Implement cache with tags to allow invalidation after assignments
        return Cache::tags(['evaluators'])->remember(
            $criteria->cacheKey(),
            self::CACHE_TTL,
            fn() => $this->repository->findAllWithCandidates($criteria)
        );
    }

    /**
     * Invalidate cache
     */
    public function invalidateCache(): void
    {
        Cache::tags(['evaluators'])->flush();
    }
}
