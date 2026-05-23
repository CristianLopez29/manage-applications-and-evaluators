<?php

namespace Src\Candidates\Application\DTOs;

use JsonSerializable;

readonly class CandidateSummaryResponse implements JsonSerializable
{
    /**
     * @param array<string, mixed> $candidateInfo
     * @param array<string, string> $complianceReport
     */
    public function __construct(
        public array $candidateInfo,
        public mixed $assignmentInfo,
        public array $complianceReport
    ) {
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'candidate_info' => $this->candidateInfo,
            'assignment_info' => $this->assignmentInfo,
            'compliance_report' => $this->complianceReport,
        ];
    }

    /** @return array<string, mixed> */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
