<?php

namespace Src\Candidates\Application\DTOs;

use JsonSerializable;

readonly class CandidateListItemResponse implements JsonSerializable
{
    public function __construct(
        public int $id,
        public string $name,
        public string $email,
        public int $yearsOfExperience,
        public ?string $primarySpecialty,
        public ?string $assignmentStatus
    ) {
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'years_of_experience' => $this->yearsOfExperience,
            'primary_specialty' => $this->primarySpecialty,
            'assignment_status' => $this->assignmentStatus,
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
