<?php

declare(strict_types=1);

namespace Src\Evaluators\Application\UseCases;

use Src\Evaluators\Domain\Repositories\AssignmentHistoryRepository;

class GetCandidateAssignmentHistory
{
    public function __construct(
        private readonly AssignmentHistoryRepository $history
    ) {
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function execute(int $candidateId): array
    {
        return $this->history->findByCandidateId($candidateId);
    }
}
