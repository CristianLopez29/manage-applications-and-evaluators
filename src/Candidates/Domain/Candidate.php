<?php

namespace Src\Candidates\Domain;

use DateTimeImmutable;
use Src\Shared\Domain\AggregateRoot;
use Src\Candidates\Domain\ValueObjects\Email;
use Src\Candidates\Domain\ValueObjects\YearsOfExperience;
use Src\Candidates\Domain\ValueObjects\CV;
use Src\Candidates\Domain\Events\CandidateRegistered;

class Candidate extends AggregateRoot
{
    private ?int $id;
    private string $name;
    private Email $email;
    private YearsOfExperience $yearsOfExperience;
    private CV $cv;
    private ?string $primarySpecialty;
    private DateTimeImmutable $createdAt;

    private function __construct(
        ?int $id,
        string $name,
        Email $email,
        YearsOfExperience $yearsOfExperience,
        CV $cv,
        ?string $primarySpecialty,
        DateTimeImmutable $createdAt
    ) {
        $this->id = $id;
        $this->name = $name;
        $this->email = $email;
        $this->yearsOfExperience = $yearsOfExperience;
        $this->cv = $cv;
        $this->primarySpecialty = $primarySpecialty;
        $this->createdAt = $createdAt;
    }

    public static function register(
        string $name,
        string $email,
        int $yearsOfExperience,
        string $cvContent,
        ?string $primarySpecialty = null
    ): self {
        $candidate = new self(
            null,
            $name,
            Email::fromString($email),
            YearsOfExperience::fromInt($yearsOfExperience),
            CV::fromString($cvContent),
            $primarySpecialty,
            new DateTimeImmutable()
        );

        $candidate->record(new CandidateRegistered(
            null,
            $candidate->email()->value(),
            $candidate->createdAt()
        ));

        return $candidate;
    }

    public static function reconstruct(
        int $id,
        string $name,
        string $email,
        int $yearsOfExperience,
        string $cvContent,
        DateTimeImmutable $createdAt,
        ?string $primarySpecialty = null
    ): self {
        return new self(
            $id,
            $name,
            Email::fromString($email),
            YearsOfExperience::fromInt($yearsOfExperience),
            CV::fromString($cvContent),
            $primarySpecialty,
            $createdAt
        );
    }

    public function id(): ?int
    {
        return $this->id;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function email(): Email
    {
        return $this->email;
    }

    public function yearsOfExperience(): YearsOfExperience
    {
        return $this->yearsOfExperience;
    }

    public function cv(): CV
    {
        return $this->cv;
    }

    public function primarySpecialty(): ?string
    {
        return $this->primarySpecialty;
    }

    public function createdAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }
}
