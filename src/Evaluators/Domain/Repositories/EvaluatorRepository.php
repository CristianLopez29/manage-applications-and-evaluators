<?php

namespace Src\Evaluators\Domain\Repositories;

use Src\Evaluators\Domain\Evaluator;
use Src\Evaluators\Domain\Criteria\ConsolidatedListCriteria;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Src\Evaluators\Application\DTO\EvaluatorWithCandidatesDTO;

interface EvaluatorRepository
{
    public function save(Evaluator $evaluator): void;

    public function findById(int $id): ?Evaluator;

    public function findByEmail(string $email): ?Evaluator;

    public function emailExists(string $email): bool;

    /**
     * @return LengthAwarePaginator<int, EvaluatorWithCandidatesDTO>
     */
    public function findAllWithCandidates(ConsolidatedListCriteria $criteria): LengthAwarePaginator;
}
