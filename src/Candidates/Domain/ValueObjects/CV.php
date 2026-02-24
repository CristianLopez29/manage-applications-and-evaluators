<?php

namespace Src\Candidates\Domain\ValueObjects;

use Src\Candidates\Domain\Exceptions\EmptyCVException;

final readonly class CV
{
    private function __construct(
        private string $content
    ) {
        $this->validate($content);
    }

    public static function fromString(string $content): self
    {
        return new self($content);
    }

    private function validate(string $content): void
    {
        // Validation moved to RequiredCVValidator to support PDF-only CVs
    }

    public function content(): string
    {
        return $this->content;
    }

    public function __toString(): string
    {
        return $this->content;
    }

    public function equals(CV $other): bool
    {
        return $this->content === $other->content;
    }
}
