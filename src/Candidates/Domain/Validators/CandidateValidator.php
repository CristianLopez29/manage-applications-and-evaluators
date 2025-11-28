<?php

namespace Src\Candidates\Domain\Validators;

use Src\Candidates\Domain\Candidate;

interface CandidateValidator
{
    public function validate(Candidate $candidate): void;

    public function setNext(CandidateValidator $validator): CandidateValidator;
}
