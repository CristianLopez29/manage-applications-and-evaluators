<?php

namespace Src\Candidates\Application\DTO;

readonly class RegisterCandidacyRequest
{
    public function __construct(
        public string $name,
        public string $email,
        public int $yearsOfExperience,
        public string $cvContent
    ) {
    }
}
