<?php

namespace Src\Candidates\Domain\ValueObjects;

final readonly class CV
{
    private function __construct(
        private string $content
    ) {
        // A CV can be empty when a candidate provides a PDF file.
        // The invariant "CV or PDF required" is verified in RequiredCVValidator.
    }

    public static function fromString(string $content): self
    {
        return new self($content);
    }

    public function isEmpty(): bool
    {
        return trim($this->content) === '';
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
