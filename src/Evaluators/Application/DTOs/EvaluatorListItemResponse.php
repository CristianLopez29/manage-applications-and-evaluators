<?php

namespace Src\Evaluators\Application\DTOs;

use JsonSerializable;

readonly class EvaluatorListItemResponse implements JsonSerializable
{
    /**
     * @param array<int, array<string, mixed>> $candidates
     */
    public function __construct(
        public int $id,
        public string $name,
        public string $email,
        public string $specialty,
        public float $averageCandidateExperience,
        public int $totalAssignedCandidates,
        public ?string $concatenatedCandidateEmails,
        public array $candidates
    ) {
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'specialty' => $this->specialty,
            'average_candidate_experience' => $this->averageCandidateExperience,
            'total_assigned_candidates' => $this->totalAssignedCandidates,
            'concatenated_candidate_emails' => $this->concatenatedCandidateEmails,
            'candidates' => $this->candidates,
        ];
    }

    /** @return array<string, mixed> */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
