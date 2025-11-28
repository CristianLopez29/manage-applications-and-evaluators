<?php

namespace Src\Candidates\Domain\Exceptions;

class EmptyCVException extends InvalidCandidateException
{
    public static function create(): self
    {
        return new self("CV cannot be empty");
    }
}
