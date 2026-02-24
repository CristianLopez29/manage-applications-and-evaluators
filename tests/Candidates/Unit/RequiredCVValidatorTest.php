<?php

namespace Tests\Candidates\Unit;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Src\Candidates\Domain\Candidate;
use Src\Candidates\Domain\Exceptions\EmptyCVException;
use Src\Candidates\Domain\Validators\RequiredCVValidator;

class RequiredCVValidatorTest extends TestCase
{
    private RequiredCVValidator $validator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->validator = new RequiredCVValidator();
    }

    #[Test]
    public function should_pass_validation_when_cv_is_present(): void
    {
        $candidate = Candidate::register(
            'Juan Pérez',
            'juan@example.com',
            5,
            'Experiencia en desarrollo backend...'
        );

        $this->validator->validate($candidate);
        $this->expectNotToPerformAssertions();
    }

    #[Test]
    public function should_fail_validation_when_cv_is_empty(): void
    {
        $this->expectException(EmptyCVException::class);

        $candidate = Candidate::register(
            'Juan Pérez',
            'juan@example.com',
            5,
            ' ' // CV empty (only spaces)
        );

        $this->validator->validate($candidate);
    }
}
