<?php

namespace Src\Candidates\Application\DTOs;

readonly class RegisterCandidacyRequest
{
    public function __construct(
        public string $name,
        public string $email,
        public int $yearsOfExperience,
        public string $cvContent,
        public ?string $cvFilePath = null,
        public ?string $primarySpecialty = null
    ) {
    }
}
