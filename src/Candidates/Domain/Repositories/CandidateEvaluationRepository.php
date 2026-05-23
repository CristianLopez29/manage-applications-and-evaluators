<?php

namespace Src\Candidates\Domain\Repositories;

use Src\Candidates\Domain\ValueObjects\EvaluationResultDTO;

interface CandidateEvaluationRepository
{
    public function save(int $candidateId, EvaluationResultDTO $result): void;

    /** @return array<string, mixed>|null */
    public function findLatestByCandidateId(int $candidateId): ?array;
}

