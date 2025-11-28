<?php

namespace Src\Candidates\Domain\ValueObjects;

use InvalidArgumentException;

final readonly class YearsOfExperience
{
    private function __construct(
        private int $value
    ) {
        $this->validate($value);
    }

    public static function fromInt(int $years): self
    {
        return new self($years);
    }

    private function validate(int $years): void
    {
        if ($years < 0) {
            throw new InvalidArgumentException("Los años de experiencia no pueden ser negativos");
        }
    }

    public function value(): int
    {
        return $this->value;
    }

    public function isGreaterThanOrEqualTo(int $minimum): bool
    {
        return $this->value >= $minimum;
    }

    public function equals(YearsOfExperience $other): bool
    {
        return $this->value === $other->value;
    }
}
