<?php

namespace Src\Evaluators\Application\DTOs;

use Src\Evaluators\Domain\Evaluator;

readonly class EvaluatorWithCandidatesDTO
{
    /**
     * @param array<int, \Src\Candidates\Domain\Candidate> $candidates
     * @param array<int, string> $assignmentsByCandidateId
     */
    public function __construct(
        public Evaluator $evaluator,
        public array $candidates,
        public float $averageExperience = 0.0,
        public ?string $concatenatedEmails = null,
        public array $assignmentsByCandidateId = []
    ) {
    }
}
