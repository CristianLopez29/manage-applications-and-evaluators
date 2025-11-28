<?php

namespace Src\Candidates\Domain\Exceptions;

class InvalidEmailException extends InvalidCandidateException
{
    public static function fromFormat(string $email): self
    {
        return new self("The email '{$email}' is not valid");
    }
}
