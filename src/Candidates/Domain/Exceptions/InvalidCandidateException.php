<?php

namespace Src\Candidates\Domain\Exceptions;

use DomainException;

class InvalidCandidateException extends DomainException
{
    public static function fromValidation(string $message): self
    {
        return new self("Invalid candidate: {$message}");
    }
}
