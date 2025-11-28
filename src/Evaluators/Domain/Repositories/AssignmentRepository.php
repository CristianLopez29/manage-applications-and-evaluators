<?php

namespace Src\Evaluators\Domain\Repositories;

use Src\Evaluators\Domain\CandidateAssignment;

interface AssignmentRepository
{
    public function save(CandidateAssignment $assignment): int;

    public function findByCandidateId(int $candidateId): ?CandidateAssignment;

    public function findByEvaluatorId(int $evaluatorId): array;

    public function existsAssignment(int $candidateId, int $evaluatorId): bool;

    public function candidateHasActiveAssignment(int $candidateId): bool;
}
