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
        /** @var \Illuminate\Pagination\LengthAwarePaginator<int, EvaluatorWithCandidatesDTO> $cached */
        $cached = $this->cache->remember(
            $criteria->cacheKey(),
            self::CACHE_TTL,
            fn() => $this->repository->findAllWithCandidates($criteria)
        );

        // through() rewrites the paginator in place and returns that same instance, so it
        // mutates whatever the cache is holding. Stores that hand back a fresh unserialised
        // copy (redis, file, database) hide this; the array store returns the object it holds,
        // so the next identical call would receive items that are already
        // EvaluatorListItemResponse and blow up on the transformer's type hint. Transform a
        // clone and leave the cached paginator with its raw DTOs.
        /** @var \Illuminate\Pagination\LengthAwarePaginator<int, EvaluatorListItemResponse> $transformed */
        $transformed = clone $cached;

        $transformed->setCollection(
            $cached->getCollection()->map(
                fn (EvaluatorWithCandidatesDTO $dto): EvaluatorListItemResponse => $this->transformer->transform($dto)
            )
        );

        return $transformed;
    }

    /**
     * Invalidate cache (kept for backward compat; prefer event-driven InvalidateEvaluatorCache listener)
     */
    public function invalidateCache(): void
    {
        $this->cache->flush();
    }
}

