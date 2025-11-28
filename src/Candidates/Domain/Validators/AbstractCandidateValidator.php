<?php

namespace Src\Candidates\Domain\Validators;

use Src\Candidates\Domain\Candidate;

abstract class AbstractCandidateValidator implements CandidateValidator
{
    private ?CandidateValidator $nextValidator = null;

    public function setNext(CandidateValidator $validator): CandidateValidator
    {
        $this->nextValidator = $validator;
        return $validator;
    }

    public function validate(Candidate $candidate): void
    {
        $this->doValidate($candidate);

        if ($this->nextValidator !== null) {
            $this->nextValidator->validate($candidate);
        }
    }

    abstract protected function doValidate(Candidate $candidate): void;
}
