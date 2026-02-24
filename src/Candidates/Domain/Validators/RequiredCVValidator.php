<?php

namespace Src\Candidates\Domain\Validators;

use Src\Candidates\Domain\Candidate;
use Src\Candidates\Domain\Exceptions\EmptyCVException;

class RequiredCVValidator extends AbstractCandidateValidator
{
    protected function doValidate(Candidate $candidate): void
    {
        try {
            $cv = $candidate->cv();
            $hasContent = trim($cv->content()) !== '';
            $hasFile = $candidate->cvFilePath() !== null;

            if (!$hasContent && !$hasFile) {
                throw EmptyCVException::create();
            }
        } catch (\Throwable $e) {
            throw EmptyCVException::create();
        }
    }
}
