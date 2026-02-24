<?php

namespace Src\Candidates\Application\DTOs;

use JsonSerializable;

readonly class CandidateSummaryResponse implements JsonSerializable
{
    public function __construct(
        public array $candidateInfo,
        public mixed $assignmentInfo,
        public array $complianceReport
    ) {
    }

    public function toArray(): array
    {
        return [
            'candidate_info' => $this->candidateInfo,
            'assignment_info' => $this->assignmentInfo,
            'compliance_report' => $this->complianceReport,
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
