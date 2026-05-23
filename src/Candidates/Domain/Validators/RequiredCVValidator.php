<?php

namespace Src\Candidates\Domain\Validators;

use Src\Candidates\Domain\Candidate;
use Src\Candidates\Domain\Exceptions\EmptyCVException;

class RequiredCVValidator extends AbstractCandidateValidator
{
    protected function doValidate(Candidate $candidate): void
    {
        $hasContent = !$candidate->cv()->isEmpty();
        $hasFile    = $candidate->cvFilePath() !== null;

        if (!$hasContent && !$hasFile) {
            throw EmptyCVException::create();
        }
    }
}
