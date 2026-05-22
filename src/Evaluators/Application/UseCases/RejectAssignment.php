<?php

declare(strict_types=1);

namespace Src\Evaluators\Application\UseCases;

use Src\Evaluators\Domain\Events\AssignmentStatusChanged;
use Src\Evaluators\Domain\Exceptions\AssignmentException;
use Src\Evaluators\Domain\Repositories\AssignmentRepository;
use Src\Shared\Domain\DomainEventPublisher;

class RejectAssignment
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

        $previous = $assignment->status()->value;
        $assignment->reject();

        $this->assignmentRepository->update($assignment);

        $this->eventPublisher->publish(new AssignmentStatusChanged(
            $assignment->id() ?? 0,
            $assignment->candidateId(),
            $assignment->evaluatorId(),
            $previous,
            $assignment->status()->value,
            new \DateTimeImmutable()
        ));
    }
}
