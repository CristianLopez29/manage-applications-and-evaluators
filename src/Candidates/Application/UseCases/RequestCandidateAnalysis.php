<?php

namespace Src\Candidates\Application\UseCases;

use Src\Candidates\Application\Ports\AiUsageBudget;
use Src\Candidates\Domain\Exceptions\AiUsageBudgetExceededException;
use Src\Candidates\Domain\Repositories\CandidateRepository;
use Src\Candidates\Infrastructure\Jobs\AnalyzeCandidateCvJob;

class RequestCandidateAnalysis
{
    public function __construct(
        private readonly CandidateRepository $candidates,
        private readonly AiUsageBudget $budget
    ) {
    }

    public function execute(int $candidateId): void
    {
        $candidate = $this->candidates->findById($candidateId);
        if ($candidate === null) {
            throw new \RuntimeException('Candidate not found');
        }

        if (!$this->budget->tryConsume()) {
            throw AiUsageBudgetExceededException::forToday();
        }

        AnalyzeCandidateCvJob::dispatch($candidateId);
    }
}
