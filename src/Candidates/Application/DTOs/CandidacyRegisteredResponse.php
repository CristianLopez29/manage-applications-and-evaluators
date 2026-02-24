<?php

namespace Src\Candidates\Application\DTOs;

use JsonSerializable;

readonly class CandidacyRegisteredResponse implements JsonSerializable
{
    public function __construct(
        public int $id,
        public string $email,
        public string $analysisStatus
    ) {
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'email' => $this->email,
            'analysis_status' => $this->analysisStatus,
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
