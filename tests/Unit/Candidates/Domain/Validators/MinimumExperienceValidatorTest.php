<?php

namespace Tests\Unit\Candidates\Domain\Validators;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Src\Candidates\Domain\Candidate;
use Src\Candidates\Domain\Exceptions\InsufficientExperienceException;
use Src\Candidates\Domain\Validators\MinimumExperienceValidator;

class MinimumExperienceValidatorTest extends TestCase
{
    private MinimumExperienceValidator $validator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->validator = new MinimumExperienceValidator();
    }

    #[Test]
    public function should_pass_validation_when_has_sufficient_experience(): void
    {
        $candidate = Candidate::register(
            'Juan Pérez',
            'juan@example.com',
            2, // Exactly the minimum
            'My CV'
        );

        $this->validator->validate($candidate);
        $this->assertTrue(true);
    }

    #[Test]
    public function should_pass_validation_when_has_more_experience(): void
    {
        $candidate = Candidate::register(
            'María García',
            'maria@example.com',
            10,
            'My CV'
        );

        $this->validator->validate($candidate);
        $this->assertTrue(true);
    }

    #[Test]
    public function should_fail_validation_when_has_less_than_two_years(): void
    {
        $this->expectException(InsufficientExperienceException::class);

        $candidate = Candidate::register(
            'Pedro López',
            'pedro@example.com',
            1, // Less than the minimum
            'My CV'
        );

        $this->validator->validate($candidate);
    }

    #[Test]
    public function should_fail_validation_when_has_zero_years(): void
    {
        $this->expectException(InsufficientExperienceException::class);

        $candidate = Candidate::register(
            'Ana Martínez',
            'ana@example.com',
            0,
            'My CV'
        );

        $this->validator->validate($candidate);
    }
}
