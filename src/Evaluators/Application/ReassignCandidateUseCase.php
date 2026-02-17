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

            $assignedToNewEvaluator = $this->assignmentRepository->findByEvaluatorId($newEvaluatorId);
            if (!$newEvaluator->canAcceptMoreCandidates(count($assignedToNewEvaluator))) {
                throw AssignmentException::evaluatorOverloaded(
                    $newEvaluatorId,
                    \Src\Evaluators\Domain\Evaluator::MAX_CONCURRENT_CANDIDATES
                );
            }

            $candidateSpecialty = $candidate->primarySpecialty();
            $evaluatorSpecialty = $newEvaluator->specialty()->value();
            if ($candidateSpecialty !== null && $candidateSpecialty !== $evaluatorSpecialty) {
                throw AssignmentException::invalidSpecialtyMatch(
                    $candidateId,
                    $candidateSpecialty,
                    $evaluatorSpecialty
                );
            }

            /** @var object{id:int,evaluator_id:int,candidate_id:int}|null $lockedCurrent */
            $lockedCurrent = DB::table('candidate_assignments')
                ->where('candidate_id', $candidateId)
                ->lockForUpdate()
                ->first();

            if ($lockedCurrent === null) {
                throw new AssignmentException("Candidate {$candidateId} does not have an existing assignment to reassign");
            }
            if ((int)$lockedCurrent->evaluator_id === $newEvaluatorId) {
                throw AssignmentException::candidateAlreadyAssigned($candidateId, $newEvaluatorId);
            }

            $this->assignmentRepository->deleteByEvaluatorAndCandidate(
                (int)$lockedCurrent->evaluator_id,
                (int)$lockedCurrent->candidate_id
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
