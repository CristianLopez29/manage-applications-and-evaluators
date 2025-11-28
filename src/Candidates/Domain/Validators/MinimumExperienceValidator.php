<?php

namespace Src\Candidates\Domain\Validators;

use Src\Candidates\Domain\Candidate;
use Src\Candidates\Domain\Exceptions\InsufficientExperienceException;

class MinimumExperienceValidator extends AbstractCandidateValidator
{
    private const MINIMUM_EXPERIENCE = 2;

    protected function doValidate(Candidate $candidate): void
    {
        $experience = $candidate->yearsOfExperience();

        if (!$experience->isGreaterThanOrEqualTo(self::MINIMUM_EXPERIENCE)) {
            throw InsufficientExperienceException::minimumRequired(
                $experience->value(),
                self::MINIMUM_EXPERIENCE
            );
        }
    }
}
