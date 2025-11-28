<?php

namespace Src\Candidates\Application;

use Src\Candidates\Application\DTO\CandidateSummaryDTO;
use Src\Candidates\Domain\Exceptions\InvalidCandidateException;
use Src\Candidates\Domain\Repositories\CandidateRepository;
use Src\Candidates\Domain\Validators\RequiredCVValidator;
use Src\Candidates\Domain\Validators\MinimumExperienceValidator;
use Src\Candidates\Domain\Validators\ValidEmailValidator;
use Src\Evaluators\Domain\Repositories\AssignmentRepository;
use Src\Evaluators\Domain\Repositories\EvaluatorRepository;

class GetCandidateSummaryUseCase
{
    public function __construct(
        private readonly CandidateRepository $candidateRepository,
        private readonly AssignmentRepository $assignmentRepository,
        private readonly EvaluatorRepository $evaluatorRepository
    ) {
    }

    public function execute(int $candidateId): CandidateSummaryDTO
    {
        // 1. Get Candidate
        $candidate = $this->candidateRepository->findById($candidateId);
        if (!$candidate) {
            throw new \RuntimeException("Candidate not found");
        }

        // 2. Get Assignment and Evaluator
        $assignmentData = null;
        $assignment = $this->assignmentRepository->findByCandidateId($candidateId);

        if ($assignment) {
            $evaluator = $this->evaluatorRepository->findById($assignment->evaluatorId());
            if ($evaluator) {
                $assignmentData = [
                    'evaluator_name' => $evaluator->name()->value(),
                    'evaluator_email' => $evaluator->email()->value(),
                    'assigned_at' => $assignment->assignedAt()->format('Y-m-d H:i:s'),
                    'status' => $assignment->status()->value()
                ];
            }
        }

        // 3. Execute Validations (Report) using Collections
        $validationResults = collect([
            'CV Required' => new RequiredCVValidator(),
            'Valid Email' => new ValidEmailValidator(),
            'Minimum Experience' => new MinimumExperienceValidator(),
        ])->map(fn($validator) => $this->checkRule($validator, $candidate))->toArray();

        return new CandidateSummaryDTO(
            $candidate->id(),
            $candidate->name(),
            $candidate->email()->value(),
            $candidate->yearsOfExperience()->value(),
            $candidate->cv()->content(),
            $assignmentData,
            $validationResults
        );
    }

    private function checkRule($validator, $candidate): string
    {
        try {
            $validator->validate($candidate);
            return 'Passed';
        } catch (InvalidCandidateException $e) {
            return 'Failed: ' . $e->getMessage();
        }
    }
}
