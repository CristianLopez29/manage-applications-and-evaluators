<?php

namespace Src\Evaluators\Domain\Exceptions;

use DomainException;

class AssignmentException extends DomainException
{
    public static function candidateAlreadyAssigned(int $candidateId, int $currentEvaluatorId): self
    {
        return new self(
            "Candidate {$candidateId} is already assigned to evaluator {$currentEvaluatorId}"
        );
    }

    public static function candidateNotFound(int $candidateId): self
    {
        return new self("Candidate with ID {$candidateId} not found");
    }

    public static function duplicateAssignment(int $candidateId, int $evaluatorId): self
    {
        return new self(
            "Assignment already exists for candidate {$candidateId} to evaluator {$evaluatorId}"
        );
    }

    public static function invalidSpecialtyMatch(
        int $candidateId,
        ?string $candidateSpecialty,
        string $evaluatorSpecialty
    ): self {
        $candidateSpec = $candidateSpecialty ?? 'none';

        return new self(
            "Cannot assign candidate {$candidateId} with specialty '{$candidateSpec}' to evaluator with specialty '{$evaluatorSpecialty}'"
        );
    }

    public static function evaluatorOverloaded(int $evaluatorId, int $max): self
    {
        return new self(
            "Evaluator {$evaluatorId} has reached the maximum number of concurrent candidates ({$max})"
        );
    }
}
