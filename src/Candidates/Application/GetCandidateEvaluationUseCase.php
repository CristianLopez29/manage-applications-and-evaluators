<?php

namespace Src\Candidates\Application;

use Src\Candidates\Domain\Repositories\CandidateEvaluationRepository;
use Src\Candidates\Domain\Repositories\CandidateRepository;

class GetCandidateEvaluationUseCase
{
    public function __construct(
        private readonly CandidateRepository $candidates,
        private readonly CandidateEvaluationRepository $evaluations
    ) {
    }

    public function execute(int $candidateId): ?array
    {
        $candidate = $this->candidates->findById($candidateId);
        if ($candidate === null) {
            throw new \RuntimeException('Candidate not found');
        }

        return $this->evaluations->findLatestByCandidateId($candidateId);
    }
}

