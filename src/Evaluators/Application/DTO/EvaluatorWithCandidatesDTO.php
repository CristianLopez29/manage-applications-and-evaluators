<?php

namespace Src\Evaluators\Application\DTO;

use Src\Evaluators\Domain\Evaluator;

readonly class EvaluatorWithCandidatesDTO
{
    public function __construct(
        public Evaluator $evaluator,
        public array $candidates,
        public float $averageExperience = 0.0,
        public ?string $concatenatedEmails = null,
        /** @var array<int,string> candidateId => assigned_at (Y-m-d H:i:s) */
        public array $assignmentsByCandidateId = []
    ) {
    }
}
