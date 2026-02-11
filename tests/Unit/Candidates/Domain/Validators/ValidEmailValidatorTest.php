<?php

namespace Tests\Unit\Candidates\Domain\Validators;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Src\Candidates\Domain\Candidate;
use Src\Candidates\Domain\Exceptions\InvalidEmailException;
use Src\Candidates\Domain\Validators\ValidEmailValidator;

class ValidEmailValidatorTest extends TestCase
{
    private ValidEmailValidator $validator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->validator = new ValidEmailValidator();
    }

    #[Test]
    public function should_pass_validation_when_email_is_valid(): void
    {
        $candidate = Candidate::register(
            'Juan Pérez',
            'juan.perez@example.com',
            3,
            'My CV here'
        );

        $this->validator->validate($candidate);
        $this->expectNotToPerformAssertions();
    }

    #[Test]
    public function should_fail_validation_when_email_is_invalid(): void
    {
        $this->expectException(InvalidEmailException::class);

        Candidate::register(
            'Juan Pérez',
            'email-invalido',
            3,
            'My CV here'
        );
    }
}
