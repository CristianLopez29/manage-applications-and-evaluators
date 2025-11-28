<?php

namespace Src\Candidates\Domain\Exceptions;

class InsufficientExperienceException extends InvalidCandidateException
{
    public static function minimumRequired(int $years, int $minimum): self
    {
        return new self("At least {$minimum} years of experience are required, but only has {$years}");
    }
}
