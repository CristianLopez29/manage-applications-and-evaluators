<?php

namespace Src\Evaluators\Application\UseCases;

use Src\Evaluators\Application\Ports\EvaluatorCachePort;
use Src\Evaluators\Domain\Repositories\EvaluatorRepository;
use Src\Evaluators\Domain\Criteria\ConsolidatedListCriteria;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

use Src\Evaluators\Application\DTOs\EvaluatorListItemResponse;
use Src\Evaluators\Application\DTOs\EvaluatorWithCandidatesDTO;
use Src\Evaluators\Application\Transformers\EvaluatorListItemTransformer;

class GetConsolidatedEvaluators
{
    private const CACHE_TTL = 300; // 5 minutes

    public function __construct(
        private readonly EvaluatorRepository $repository,
        private readonly EvaluatorListItemTransformer $transformer,
        private readonly EvaluatorCachePort $cache,
    ) {
    }

    /**
     * @return LengthAwarePaginator<int, EvaluatorListItemResponse>
     */
    public function execute(ConsolidatedListCriteria $criteria): LengthAwarePaginator
    {
        /** @var \Illuminate\Pagination\LengthAwarePaginator<int, EvaluatorWithCandidatesDTO> $paginator */
        $paginator = $this->cache->remember(
            $criteria->cacheKey(),
            self::CACHE_TTL,
            fn() => $this->repository->findAllWithCandidates($criteria)
        );

        $paginator->through(fn(EvaluatorWithCandidatesDTO $dto) => $this->transformer->transform($dto));

        return $paginator;
    }

    /**
     * Invalidate cache (kept for backward compat; prefer event-driven InvalidateEvaluatorCache listener)
     */
    public function invalidateCache(): void
    {
        $this->cache->flush();
    }
}

