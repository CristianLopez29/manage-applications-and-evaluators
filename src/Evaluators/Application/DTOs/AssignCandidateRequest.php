<?php

namespace Src\Evaluators\Application\DTOs;

readonly class AssignCandidateRequest
{
    public function __construct(
        public int $candidateId,
        public int $evaluatorId
    ) {
    }
}
