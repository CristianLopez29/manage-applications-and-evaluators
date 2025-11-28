<?php

namespace Src\Evaluators\Domain\Exceptions;

use DomainException;

class InvalidEvaluatorException extends DomainException
{
    public static function fromValidation(string $message): self
    {
        return new self("Evaluator invalid: {$message}");
    }
}
