<?php

namespace Src\Candidates\Application\DTO;

readonly class CandidateSummaryDTO
{
    public function __construct(
        public int $id,
        public string $name,
        public string $email,
        public int $yearsOfExperience,
        public string $cvContent,
        public ?array $assignment, // Data of the evaluator assigned
        public array $validationResults // ['rule' => 'Pass/Fail']
    ) {
    }
}
