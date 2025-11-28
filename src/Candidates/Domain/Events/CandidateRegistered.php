<?php

namespace Src\Candidates\Domain\Events;

use Src\Shared\Domain\DomainEvent;

readonly class CandidateRegistered implements DomainEvent
{
    public function __construct(
        public ?int $candidateId,
        public string $email,
        public \DateTimeImmutable $occurredOn
    ) {
    }

    public function occurredOn(): \DateTimeImmutable
    {
        return $this->occurredOn;
    }
}
