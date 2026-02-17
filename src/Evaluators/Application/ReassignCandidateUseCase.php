<?php

namespace Src\Evaluators\Application;

use Illuminate\Support\Facades\DB;
use Src\Candidates\Domain\Repositories\CandidateRepository;
use Src\Evaluators\Domain\CandidateAssignment;
use Src\Evaluators\Domain\Exceptions\AssignmentException;
use Src\Evaluators\Domain\Exceptions\EvaluatorNotFoundException;
use Src\Evaluators\Domain\Repositories\AssignmentRepository;
use Src\Evaluators\Domain\Repositories\EvaluatorRepository;

class ReassignCandidateUseCase
{
    public function __construct(
        private readonly CandidateRepository $candidateRepository,
        private readonly EvaluatorRepository $evaluatorRepository,
        private readonly AssignmentRepository $assignmentRepository,
        private readonly GetConsolidatedEvaluatorsUseCase $consolidatedUseCase
    ) {
    }

    public function execute(int $newEvaluatorId, int $candidateId): void
    {
        DB::transaction(function () use ($newEvaluatorId, $candidateId) {
            $candidate = $this->candidateRepository->findById($candidateId);
            if (!$candidate) {
                throw AssignmentException::candidateNotFound($candidateId);
            }

            $newEvaluator = $this->evaluatorRepository->findById($newEvaluatorId);
            if (!$newEvaluator) {
                throw EvaluatorNotFoundException::withId($newEvaluatorId);
            }

            $existingAssignment = $this->assignmentRepository->findByCandidateId($candidateId);
            if (!$existingAssignment) {
                throw new AssignmentException("Candidate {$candidateId} does not have an existing assignment to reassign");
            }

            $this->assignmentRepository->deleteByEvaluatorAndCandidate(
                $existingAssignment->evaluatorId(),
                $existingAssignment->candidateId()
            );

            $newAssignment = CandidateAssignment::create(
                $candidateId,
                $newEvaluatorId
            );

            $assignmentId = $this->assignmentRepository->save($newAssignment);

            event(new \Src\Evaluators\Domain\Events\CandidateAssigned(
                $assignmentId,
                $candidateId,
                $newEvaluatorId,
                new \DateTimeImmutable()
            ));
        });

        $this->consolidatedUseCase->invalidateCache();
    }
}

