<?php

namespace Src\Candidates\Domain\Events;

use DateTimeImmutable;

final class CandidateAnalysisCompleted
{
    public function __construct(
        public int $candidateId,
        public DateTimeImmutable $occurredOn
    ) {
    }
}

