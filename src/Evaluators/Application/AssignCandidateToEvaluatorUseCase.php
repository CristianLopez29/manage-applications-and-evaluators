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
        // Use transaction with pessimistic lock to prevent race conditions
        DB::transaction(function () use ($request) {
            // 1. Verify that the candidate exists
            $candidate = $this->candidateRepository->findById($request->candidateId);
            if (!$candidate) {
                throw AssignmentException::candidateNotFound($request->candidateId);
            }

            // 2. Verify that the evaluator exists
            $evaluator = $this->evaluatorRepository->findById($request->evaluatorId);
            if (!$evaluator) {
                throw EvaluatorNotFoundException::withId($request->evaluatorId);
            }

            // 3. Verify that the candidate is not already assigned
            // Use lockForUpdate to block the row and prevent concurrent assignments
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

            // 4. Create the assignment
            $assignment = CandidateAssignment::create(
                $request->candidateId,
                $request->evaluatorId
            );

            // 5. Persist the assignment
            $assignmentId = $this->assignmentRepository->save($assignment);

            // 6. Dispatch Domain Event
            event(new \Src\Evaluators\Domain\Events\CandidateAssigned(
                $assignmentId,
                $request->candidateId,
                $request->evaluatorId,
                new \DateTimeImmutable()
            ));
        });

        // 6. Invalidate cache after successful assignment
        $this->consolidatedUseCase->invalidateCache();
    }
}
