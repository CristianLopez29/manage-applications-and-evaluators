<?php

namespace Src\Evaluators\Application\DTOs;

use JsonSerializable;

readonly class AssignmentResponse implements JsonSerializable
{
    public function __construct(
        public int $candidateId,
        public int $evaluatorId
    ) {
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'candidate_id' => $this->candidateId,
            'evaluator_id' => $this->evaluatorId,
        ];
    }

    /** @return array<string, mixed> */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
