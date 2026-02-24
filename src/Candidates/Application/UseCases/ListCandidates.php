<?php

namespace Src\Candidates\Application\UseCases;

use Src\Candidates\Application\DTOs\CandidateListItemResponse;
use Src\Candidates\Application\Transformers\CandidateListItemTransformer;
use Src\Candidates\Domain\Repositories\CandidateRepository;
use Src\Evaluators\Domain\Enums\AssignmentStatus;
use Src\Evaluators\Domain\Repositories\AssignmentRepository;

class ListCandidates
{
    public function __construct(
        private readonly CandidateRepository $candidateRepository,
        private readonly AssignmentRepository $assignmentRepository,
        private readonly CandidateListItemTransformer $transformer
    ) {
    }

    /**
     * @return array<int, CandidateListItemResponse>
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
        } elseif ($status && AssignmentStatus::tryFrom($status)) {
            $statusEnum = AssignmentStatus::from($status);
            $candidates = array_filter($candidates, function ($candidate) use ($statusEnum) {
                $id = $candidate->id();
                if ($id === null) {
                    return false;
                }
                $assignment = $this->assignmentRepository->findByCandidateId($id);
                return $assignment !== null && $assignment->status() === $statusEnum;
            });
        }

        return array_map(function ($candidate) {
            $id = $candidate->id();

            $assignment = null;
            if ($id !== null) {
                $assignment = $this->assignmentRepository->findByCandidateId($id);
            }

            return $this->transformer->transform($candidate, $assignment);
        }, $candidates);
    }
}
