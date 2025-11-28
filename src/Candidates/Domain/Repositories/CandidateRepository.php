<?php

namespace Src\Candidates\Domain\Repositories;

use Src\Candidates\Domain\Candidate;

interface CandidateRepository
{
    public function save(Candidate $candidate): void;

    public function findById(int $id): ?Candidate;
}
