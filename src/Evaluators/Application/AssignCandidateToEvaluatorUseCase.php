<?php

namespace Src\Evaluators\Application;

use Src\Candidates\Domain\Repositories\CandidateRepository;
use Src\Evaluators\Application\DTO\AssignCandidateRequest;
use Src\Evaluators\Domain\CandidateAssignment;
use Src\Evaluators\Domain\Exceptions\AssignmentException;
use Src\Evaluators\Domain\Exceptions\EvaluatorNotFoundException;
use Src\Evaluators\Domain\Repositories\AssignmentRepository;
use Src\Evaluators\Domain\Repositories\EvaluatorRepository;
use Illuminate\Support\Facades\DB;

class AssignCandidateToEvaluatorUseCase
{
    public function __construct(
        private readonly CandidateRepository $candidateRepository,
        private readonly EvaluatorRepository $evaluatorRepository,
        private readonly AssignmentRepository $assignmentRepository,
        private readonly GetConsolidatedEvaluatorsUseCase $consolidatedUseCase
    ) {
    }

    public function execute(AssignCandidateRequest $request): void
    {
        DB::transaction(function () use ($request) {
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
            $evaluatorSpecialty = $evaluator->specialty()->value();
            if ($candidateSpecialty === null || $candidateSpecialty !== $evaluatorSpecialty) {
                throw AssignmentException::invalidSpecialtyMatch(
                    $request->candidateId,
                    $candidateSpecialty,
                    $evaluatorSpecialty
                );
            }

            $existingAssignment = DB::table('candidate_assignments')
                ->where('candidate_id', $request->candidateId)
                ->lockForUpdate()
                ->first();

            if ($existingAssignment) {
                /** @var object{evaluator_id: int} $existingAssignment */
                throw AssignmentException::candidateAlreadyAssigned(
                    $request->candidateId,
                    $existingAssignment->evaluator_id
                );
            }

            $assignment = CandidateAssignment::create(
                $request->candidateId,
                $request->evaluatorId
            );

            $assignmentId = $this->assignmentRepository->save($assignment);

            event(new \Src\Evaluators\Domain\Events\CandidateAssigned(
                $assignmentId,
                $request->candidateId,
                $request->evaluatorId,
                new \DateTimeImmutable()
            ));
        });

        $this->consolidatedUseCase->invalidateCache();
    }
}
