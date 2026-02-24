<?php

namespace Src\Evaluators\Domain;

use DateTimeImmutable;
use Src\Candidates\Domain\ValueObjects\Email;
use Src\Evaluators\Domain\ValueObjects\EvaluatorName;
use Src\Evaluators\Domain\Enums\Specialty;

class Evaluator
{
    public const MAX_CONCURRENT_CANDIDATES = 10;

    private function __construct(
        private ?int $id,
        private EvaluatorName $name,
        private Email $email,
        private Specialty $specialty,
        private DateTimeImmutable $createdAt
    ) {
    }

    // Factory method to create a new evaluator
    public static function register(
        string $name,
        string $email,
        string $specialty
    ): self {
        return new self(
            null, // ID is assigned when persisting
            EvaluatorName::fromString($name),
            Email::fromString($email),
            Specialty::from($specialty),
            new DateTimeImmutable()
        );
    }

    // Factory method to reconstruct from persistence
    public static function reconstruct(
        int $id,
        string $name,
        string $email,
        string $specialty,
        DateTimeImmutable $createdAt
    ): self {
        return new self(
            $id,
            EvaluatorName::fromString($name),
            Email::fromString($email),
            Specialty::from($specialty),
            $createdAt
        );
    }

    // Getters
    public function id(): ?int
    {
        return $this->id;
    }

    public function name(): EvaluatorName
    {
        return $this->name;
    }

    public function email(): Email
    {
        return $this->email;
    }

    public function specialty(): Specialty
    {
        return $this->specialty;
    }

    public function createdAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function canAcceptMoreCandidates(int $currentAssigned): bool
    {
        return $currentAssigned < self::MAX_CONCURRENT_CANDIDATES;
    }

    public function withId(int $id): self
    {
        return new self(
            $id,
            $this->name,
            $this->email,
            $this->specialty,
            $this->createdAt
        );
    }
}
