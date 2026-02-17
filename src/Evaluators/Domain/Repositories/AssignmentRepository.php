<?php

namespace Src\Evaluators\Domain\Repositories;

use Src\Evaluators\Domain\CandidateAssignment;

interface AssignmentRepository
{
    public function save(CandidateAssignment $assignment): int;

    public function update(CandidateAssignment $assignment): void;

    public function findByCandidateId(int $candidateId): ?CandidateAssignment;

    /**
     * @return array<int, CandidateAssignment>
     */
    public function findByEvaluatorId(int $evaluatorId): array;

    public function findByEvaluatorAndCandidate(int $evaluatorId, int $candidateId): ?CandidateAssignment;

    public function existsAssignment(int $candidateId, int $evaluatorId): bool;

    public function candidateHasActiveAssignment(int $candidateId): bool;

    public function deleteByEvaluatorAndCandidate(int $evaluatorId, int $candidateId): void;
}
