<?php

namespace Src\Candidates\Application\DTO;

readonly class CandidateSummaryDTO
{
    /**
     * @param array<string, mixed>|null $assignment
     * @param array<string, string> $validationResults
     */
    public function __construct(
        public int $id,
        public string $name,
        public string $email,
        public int $yearsOfExperience,
        public string $cvContent,
        public bool $hasPdf,
        public ?array $assignment, // Data of the evaluator assigned
        public array $validationResults // ['rule' => 'Pass/Fail']
    ) {
    }
}
