<?php

namespace Src\Evaluators\Domain\ValueObjects;

use InvalidArgumentException;

final readonly class EvaluatorName
{
    private function __construct(
        private string $value
    ) {
        $this->validate($value);
    }

    public static function fromString(string $name): self
    {
        return new self($name);
    }

    private function validate(string $name): void
    {
        if (trim($name) === '') {
            throw new InvalidArgumentException("The evaluator name cannot be empty");
        }

        if (strlen($name) < 3) {
            throw new InvalidArgumentException("The evaluator name must have at least 3 characters");
        }

        if (strlen($name) > 255) {
            throw new InvalidArgumentException("The evaluator name cannot exceed 255 characters");
        }
    }

    public function value(): string
    {
        return $this->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }

    public function equals(EvaluatorName $other): bool
    {
        return $this->value === $other->value;
    }
}
