<?php

namespace Src\Evaluators\Application\DTOs;

use JsonSerializable;

readonly class EvaluatorCandidateResponse implements JsonSerializable
{
    public function __construct(
        public int $id,
        public string $name,
        public string $email,
        public int $yearsOfExperience,
        public string $assignedAt,
        public string $status
    ) {
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'years_of_experience' => $this->yearsOfExperience,
            'assigned_at' => $this->assignedAt,
            'status' => $this->status,
        ];
    }

    /** @return array<string, mixed> */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
