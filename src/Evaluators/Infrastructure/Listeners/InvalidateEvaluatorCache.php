<?php

namespace Src\Evaluators\Infrastructure\Listeners;

use Src\Evaluators\Application\Ports\EvaluatorCachePort;
use Src\Evaluators\Domain\Events\CandidateAssigned;
use Src\Evaluators\Domain\Events\AssignmentStatusChanged;

class InvalidateEvaluatorCache
{
    public function __construct(
        private readonly EvaluatorCachePort $cache
    ) {
    }

    public function handle(CandidateAssigned|AssignmentStatusChanged $event): void
    {
        $this->cache->flush();
    }
}
