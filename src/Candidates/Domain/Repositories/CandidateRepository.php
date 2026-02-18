<?php

namespace Src\Candidates\Domain\Repositories;

use Src\Candidates\Domain\Candidate;

interface CandidateRepository
{
    public function save(Candidate $candidate): int;

    public function findById(int $id): ?Candidate;

    /**
     * @return array<int, Candidate>
     */
    public function search(
        ?int $minExperience,
        ?string $emailContains,
        ?string $primarySpecialty
    ): array;
}
