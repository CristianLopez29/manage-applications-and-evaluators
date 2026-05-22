<?php

declare(strict_types=1);

namespace Src\Evaluators\Application\UseCases;

use Src\Candidates\Domain\Repositories\CandidateRepository;
use Src\Evaluators\Application\DTOs\AssignCandidateRequest;
use Src\Evaluators\Domain\CandidateAssignment;
use Src\Evaluators\Domain\Events\CandidateAssigned;
use Src\Evaluators\Domain\Exceptions\AssignmentException;
use Src\Evaluators\Domain\Exceptions\EvaluatorNotFoundException;
use Src\Evaluators\Domain\Repositories\AssignmentRepository;
use Src\Evaluators\Domain\Repositories\EvaluatorRepository;
use Src\Shared\Application\Ports\TransactionManager;
use Src\Shared\Domain\DomainEventPublisher;

class AssignCandidateToEvaluator
{
    public function __construct(
        private readonly CandidateRepository $candidateRepository,
        private readonly EvaluatorRepository $evaluatorRepository,
        private readonly AssignmentRepository $assignmentRepository,
        private readonly TransactionManager $transactionManager,
        private readonly DomainEventPublisher $eventPublisher,
    ) {
    }

    public function execute(AssignCandidateRequest $request): void
    {
        $candidate = $this->candidateRepository->findById($request->candidateId);
        if (!$candidate) {
            throw AssignmentException::candidateNotFound($request->candidateId);
        }

        $evaluator = $this->evaluatorRepository->findById($request->evaluatorId);
        if (!$evaluator) {
            throw EvaluatorNotFoundException::withId($request->evaluatorId);
        }

        $assignedToEvaluator = $this->assignmentRepository->findByEvaluatorId($request->evaluatorId);
        if (!$evaluator->canAcceptMoreCandidates(count($assignedToEvaluator))) {
            throw AssignmentException::evaluatorOverloaded(
                $request->evaluatorId,
                \Src\Evaluators\Domain\Evaluator::MAX_CONCURRENT_CANDIDATES
            );
        }

        $candidateSpecialty = $candidate->primarySpecialty();
        $evaluatorSpecialty = $evaluator->specialty()->value;
        if ($candidateSpecialty !== null && $candidateSpecialty !== $evaluatorSpecialty) {
            throw AssignmentException::invalidSpecialtyMatch(
                $request->candidateId,
                $candidateSpecialty,
                $evaluatorSpecialty
            );
        }

        // Pessimistic lock inside a transaction prevents double-assignment races
        $publishedEvent = null;
        $this->transactionManager->run(function () use ($request, &$publishedEvent) {
            $existing = $this->assignmentRepository->findByCandidateIdForUpdate($request->candidateId);

            if ($existing) {
                throw AssignmentException::candidateAlreadyAssigned(
                    $request->candidateId,
                    $existing->evaluatorId()
                );
            }

            $assignment = CandidateAssignment::create(
                $request->candidateId,
                $request->evaluatorId
            );

            $assignmentId = $this->assignmentRepository->save($assignment);

            $publishedEvent = new CandidateAssigned(
                $assignmentId,
                $request->candidateId,
                $request->evaluatorId,
                new \DateTimeImmutable()
            );
        });

        if ($publishedEvent !== null) {
            $this->eventPublisher->publish($publishedEvent);
        }
    }
}
