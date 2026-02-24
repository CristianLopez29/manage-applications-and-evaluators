<?php

namespace Tests\Evaluators\Unit;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Src\Evaluators\Domain\Enums\Specialty;
use ValueError;

class SpecialtyTest extends TestCase
{
    #[Test]
    public function should_have_correct_values(): void
    {
        $this->assertEquals('Backend', Specialty::BACKEND->value);
        $this->assertEquals('Frontend', Specialty::FRONTEND->value);
        $this->assertEquals('Fullstack', Specialty::FULLSTACK->value);
        $this->assertEquals('DevOps', Specialty::DEVOPS->value);
        $this->assertEquals('Mobile', Specialty::MOBILE->value);
        $this->assertEquals('QA', Specialty::QA->value);
        $this->assertEquals('Data', Specialty::DATA->value);
        $this->assertEquals('Security', Specialty::SECURITY->value);
    }

    #[Test]
    public function should_create_from_valid_string(): void
    {
        $this->assertEquals(Specialty::BACKEND, Specialty::from('Backend'));
        $this->assertEquals(Specialty::FRONTEND, Specialty::from('Frontend'));
    }

    #[Test]
    public function should_throw_exception_for_invalid_value(): void
    {
        $this->expectException(ValueError::class);
        Specialty::from('Invalid');
    }
}
