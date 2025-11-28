<?php

namespace Src\Candidates\Domain\Validators;

use Src\Candidates\Domain\Candidate;
use Src\Candidates\Domain\Exceptions\EmptyCVException;

class RequiredCVValidator extends AbstractCandidateValidator
{
    protected function doValidate(Candidate $candidate): void
    {
        // Validation already done in the CV Value Object, we only need to verify that we can access the CV
        try {
            $cv = $candidate->cv();
            if (trim($cv->content()) === '') {
                throw EmptyCVException::create();
            }
        } catch (\Throwable $e) {
            throw EmptyCVException::create();
        }
    }
}
