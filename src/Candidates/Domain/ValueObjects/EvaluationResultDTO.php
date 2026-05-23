<?php

namespace Src\Candidates\Domain\ValueObjects;

final readonly class EvaluationResultDTO
{
    /**
     * @param array<int, string>|null $skills
     * @param array<string, mixed> $rawResponse
     */
    public function __construct(
        public ?string $summary,
        public ?array $skills,
        public ?int $yearsExperience,
        public ?string $seniorityLevel,
        public array $rawResponse
    ) {
    }
}

