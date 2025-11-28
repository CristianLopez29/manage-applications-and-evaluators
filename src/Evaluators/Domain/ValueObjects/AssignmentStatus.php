<?php

namespace Src\Evaluators\Domain\ValueObjects;

use InvalidArgumentException;

final readonly class AssignmentStatus
{
    private const PENDING = 'pending';
    private const IN_PROGRESS = 'in_progress';
    private const COMPLETED = 'completed';
    private const REJECTED = 'rejected';

    private const VALID_STATUSES = [
        self::PENDING,
        self::IN_PROGRESS,
        self::COMPLETED,
        self::REJECTED,
    ];

    private function __construct(
        private string $value
    ) {
        $this->validate($value);
    }

    public static function fromString(string $status): self
    {
        return new self($status);
    }

    public static function pending(): self
    {
        return new self(self::PENDING);
    }

    public static function inProgress(): self
    {
        return new self(self::IN_PROGRESS);
    }

    public static function completed(): self
    {
        return new self(self::COMPLETED);
    }

    public static function rejected(): self
    {
        return new self(self::REJECTED);
    }

    private function validate(string $status): void
    {
        if (!in_array($status, self::VALID_STATUSES, true)) {
            throw new InvalidArgumentException(
                "Estado inválido. Valores permitidos: " . implode(', ', self::VALID_STATUSES)
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

    public function equals(AssignmentStatus $other): bool
    {
        return $this->value === $other->value;
    }

    public function isPending(): bool
    {
        return $this->value === self::PENDING;
    }

    public function isInProgress(): bool
    {
        return $this->value === self::IN_PROGRESS;
    }

    public function isCompleted(): bool
    {
        return $this->value === self::COMPLETED;
    }

    public function isRejected(): bool
    {
        return $this->value === self::REJECTED;
    }
}
