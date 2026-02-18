<?php

namespace Src\Candidates\Domain\ValueObjects;

final readonly class EvaluationResultDTO
{
    public function __construct(
        public ?string $summary,
        /** @var array<int, string>|null */
        public ?array $skills,
        public ?int $yearsExperience,
        public ?string $seniorityLevel,
        public array $rawResponse
    ) {
    }
}

