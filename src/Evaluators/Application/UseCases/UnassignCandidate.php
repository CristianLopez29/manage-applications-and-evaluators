<?php

declare(strict_types=1);

namespace Src\Evaluators\Application\UseCases;

use Src\Evaluators\Domain\Events\AssignmentStatusChanged;
use Src\Evaluators\Domain\Exceptions\AssignmentException;
use Src\Evaluators\Domain\Repositories\AssignmentRepository;
use Src\Shared\Domain\DomainEventPublisher;

class UnassignCandidate
{
    public function __construct(
        private readonly AssignmentRepository $assignmentRepository,
        private readonly DomainEventPublisher $eventPublisher,
    ) {
    }

    public function execute(int $evaluatorId, int $candidateId): void
    {
        $assignment = $this->assignmentRepository->findByEvaluatorAndCandidate($evaluatorId, $candidateId);

        if (!$assignment) {
            throw new AssignmentException("Assignment not found for evaluator {$evaluatorId} and candidate {$candidateId}");
        }

        $this->assignmentRepository->deleteByEvaluatorAndCandidate($evaluatorId, $candidateId);

        $this->eventPublisher->publish(new AssignmentStatusChanged(
            $assignment->id() ?? 0,
            $assignment->candidateId(),
            $assignment->evaluatorId(),
            $assignment->status()->value,
            'unassigned',
            new \DateTimeImmutable()
        ));
    }
}
