<?php

namespace Src\Candidates\Application;

use Src\Candidates\Domain\Repositories\CandidateRepository;
use Src\Evaluators\Domain\Repositories\AssignmentRepository;

class ListCandidatesUseCase
{
    public function __construct(
        private readonly CandidateRepository $candidateRepository,
        private readonly AssignmentRepository $assignmentRepository
    ) {
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function execute(
        ?string $status,
        ?int $minExperience,
        ?string $emailContains,
        ?string $primarySpecialty
    ): array {
        $candidates = $this->candidateRepository->search(
            $minExperience,
            $emailContains,
            $primarySpecialty
        );

        if ($status === 'unassigned') {
            $candidates = array_filter($candidates, function ($candidate) {
                $id = $candidate->id();
                if ($id === null) {
                    return false;
                }

                return !$this->assignmentRepository->candidateHasActiveAssignment($id);
            });
        } elseif (in_array($status, ['pending', 'in_progress', 'completed', 'rejected'], true)) {
            $candidates = array_filter($candidates, function ($candidate) use ($status) {
                $id = $candidate->id();
                if ($id === null) {
                    return false;
                }
                $assignment = $this->assignmentRepository->findByCandidateId($id);
                return $assignment !== null && $assignment->status()->value() === $status;
            });
        }

        return array_map(function ($candidate) {
            $id = $candidate->id();

            $assignment = null;
            if ($id !== null) {
                $assignment = $this->assignmentRepository->findByCandidateId($id);
            }

            return [
                'id' => $id,
                'name' => $candidate->name(),
                'email' => $candidate->email()->value(),
                'years_of_experience' => $candidate->yearsOfExperience()->value(),
                'primary_specialty' => $candidate->primarySpecialty(),
                'assignment_status' => $assignment ? $assignment->status()->value() : null,
            ];
        }, $candidates);
    }
}
