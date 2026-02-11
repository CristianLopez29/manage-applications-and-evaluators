<?php

namespace Src\Evaluators\Domain\ValueObjects;

use InvalidArgumentException;

final readonly class Specialty
{
    private const VALID_SPECIALTIES = [
        'Backend',
        'Frontend',
        'Fullstack',
        'DevOps',
        'Mobile',
        'QA',
        'Data',
        'Security'
    ];

    private function __construct(
        private string $value
    ) {
        $this->validate($value);
    }

    public static function fromString(string $specialty): self
    {
        return new self($specialty);
    }

    private function validate(string $specialty): void
    {
        if (trim($specialty) === '') {
            throw new InvalidArgumentException("The specialty cannot be empty");
        }

        if (!in_array($specialty, self::VALID_SPECIALTIES, true)) {
            throw new InvalidArgumentException(
                "Invalid specialty. Allowed values: " . implode(', ', self::VALID_SPECIALTIES)
            );
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

    public function equals(Specialty $other): bool
    {
        return $this->value === $other->value;
    }

    /**
     * @return array<int, string>
     */
    public static function validSpecialties(): array
    {
        return self::VALID_SPECIALTIES;
    }
}
