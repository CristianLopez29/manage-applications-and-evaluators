<?php

namespace Src\Evaluators\Domain\Exceptions;

class EvaluatorNotFoundException extends InvalidEvaluatorException
{
    public static function withId(int $id): self
    {
        return new self("Evaluator with ID {$id} not found");
    }

    public static function withEmail(string $email): self
    {
        return new self("Evaluator with email '{$email}' not found");
    }
}
