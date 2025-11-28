<?php

namespace Src\Evaluators\Application\DTO;

readonly class AssignCandidateRequest
{
    public function __construct(
        public int $candidateId,
        public int $evaluatorId
    ) {
    }
}
