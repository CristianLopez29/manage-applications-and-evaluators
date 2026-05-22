<?php

declare(strict_types=1);

namespace Src\Evaluators\Application\UseCases;

use Src\Candidates\Domain\Repositories\CandidateRepository;
use Src\Evaluators\Domain\CandidateAssignment;
use Src\Evaluators\Domain\Events\CandidateAssigned;
use Src\Evaluators\Domain\Exceptions\AssignmentException;
use Src\Evaluators\Domain\Exceptions\EvaluatorNotFoundException;
use Src\Evaluators\Domain\Repositories\AssignmentRepository;
use Src\Evaluators\Domain\Repositories\EvaluatorRepository;
use Src\Shared\Application\Ports\TransactionManager;
use Src\Shared\Domain\DomainEventPublisher;

class ReassignCandidate
{
    public function __construct(
        private readonly CandidateRepository $candidateRepository,
        private readonly EvaluatorRepository $evaluatorRepository,
        private readonly AssignmentRepository $assignmentRepository,
        private readonly TransactionManager $transactionManager,
        private readonly DomainEventPublisher $eventPublisher,
    ) {
    }

    public function execute(int $newEvaluatorId, int $candidateId): void
    {
        $candidate = $this->candidateRepository->findById($candidateId);
        if (!$candidate) {
            throw AssignmentException::candidateNotFound($candidateId);
        }

        $newEvaluator = $this->evaluatorRepository->findById($newEvaluatorId);
        if (!$newEvaluator) {
            throw EvaluatorNotFoundException::withId($newEvaluatorId);
        }

        $assignedToNewEvaluator = $this->assignmentRepository->findByEvaluatorId($newEvaluatorId);
        if (!$newEvaluator->canAcceptMoreCandidates(count($assignedToNewEvaluator))) {
            throw AssignmentException::evaluatorOverloaded(
                $newEvaluatorId,
                \Src\Evaluators\Domain\Evaluator::MAX_CONCURRENT_CANDIDATES
            );
        }

        $candidateSpecialty = $candidate->primarySpecialty();
        $evaluatorSpecialty = $newEvaluator->specialty()->value;
        if ($candidateSpecialty !== null && $candidateSpecialty !== $evaluatorSpecialty) {
            throw AssignmentException::invalidSpecialtyMatch(
                $candidateId,
                $candidateSpecialty,
                $evaluatorSpecialty
            );
        }

        // Pessimistic lock inside a transaction prevents concurrent reassignment races
        $publishedEvent = null;
        $this->transactionManager->run(function () use ($newEvaluatorId, $candidateId, &$publishedEvent) {
            /** @var \Src\Evaluators\Domain\CandidateAssignment|null $lockedCurrent */
            $lockedCurrent = $this->assignmentRepository->findByCandidateIdForUpdate($candidateId);

            if ($lockedCurrent === null) {
                throw new AssignmentException("Candidate {$candidateId} does not have an existing assignment to reassign");
            }

            if ($lockedCurrent->evaluatorId() === $newEvaluatorId) {
                throw AssignmentException::candidateAlreadyAssigned($candidateId, $newEvaluatorId);
            }

            $this->assignmentRepository->deleteByEvaluatorAndCandidate(
                $lockedCurrent->evaluatorId(),
                $lockedCurrent->candidateId()
            );

            $newAssignment = CandidateAssignment::create($candidateId, $newEvaluatorId);
            $assignmentId  = $this->assignmentRepository->save($newAssignment);

            $publishedEvent = new CandidateAssigned(
                $assignmentId,
                $candidateId,
                $newEvaluatorId,
                new \DateTimeImmutable()
            );
        });

        if ($publishedEvent !== null) {
            $this->eventPublisher->publish($publishedEvent);
        }
    }
}
