<?php

namespace Src\Candidates\Domain\Validators;

use Src\Candidates\Domain\Candidate;
use Src\Candidates\Domain\Exceptions\InvalidEmailException;

class ValidEmailValidator extends AbstractCandidateValidator
{
    protected function doValidate(Candidate $candidate): void
    {
        // Validation already done in the Email Value Object, we only need to verify that we can access the Email
        try {
            $email = $candidate->email();
            if (!filter_var($email->value(), FILTER_VALIDATE_EMAIL)) {
                throw InvalidEmailException::fromFormat($email->value());
            }
        } catch (\Throwable $e) {
            throw $e;
        }
    }
}
