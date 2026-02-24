<?php

namespace Src\Candidates\Application\UseCases;

use Src\Candidates\Domain\Repositories\CandidateRepository;
use Src\Candidates\Infrastructure\Jobs\AnalyzeCandidateCvJob;

class RequestCandidateAnalysis
{
    public function __construct(
        private readonly CandidateRepository $candidates
    ) {
    }

    public function execute(int $candidateId): void
    {
        $candidate = $this->candidates->findById($candidateId);
        if ($candidate === null) {
            throw new \RuntimeException('Candidate not found');
        }

        AnalyzeCandidateCvJob::dispatch($candidateId);
    }
}
