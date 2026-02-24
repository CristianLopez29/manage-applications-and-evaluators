<?php

namespace Src\Evaluators\Application\UseCases;

use Src\Evaluators\Domain\Repositories\EvaluatorRepository;
use Src\Evaluators\Domain\Criteria\ConsolidatedListCriteria;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;

use Src\Evaluators\Application\DTOs\EvaluatorWithCandidatesDTO;
use Src\Evaluators\Application\Transformers\EvaluatorListItemTransformer;

class GetConsolidatedEvaluators
{
    private const CACHE_TTL = 300; // 5 minutos

    public function __construct(
        private readonly EvaluatorRepository $repository,
        private readonly EvaluatorListItemTransformer $transformer
    ) {
    }

    /**
     * @return LengthAwarePaginator
     */
    public function execute(ConsolidatedListCriteria $criteria): LengthAwarePaginator
    {
        // Implement cache with tags to allow invalidation after assignments
        $paginator = Cache::tags(['evaluators'])->remember(
            $criteria->cacheKey(),
            self::CACHE_TTL,
            fn() => $this->repository->findAllWithCandidates($criteria)
        );

        $paginator->through(fn(EvaluatorWithCandidatesDTO $dto) => $this->transformer->transform($dto));

        return $paginator;
    }

    /**
     * Invalidate cache
     */
    public function invalidateCache(): void
    {
        Cache::tags(['evaluators'])->flush();
    }
}
