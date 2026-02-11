<?php

namespace Tests\Unit\Evaluators\Domain\ValueObjects;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use InvalidArgumentException;
use Src\Evaluators\Domain\ValueObjects\Specialty;

class SpecialtyTest extends TestCase
{
    #[Test]
    public function should_create_valid_specialty(): void
    {
        $specialty = Specialty::fromString('Backend');

        $this->assertEquals('Backend', $specialty->value());
    }

    #[Test]
    public function should_accept_all_valid_specialties(): void
    {
        $validSpecialties = ['Backend', 'Frontend', 'Fullstack', 'DevOps', 'Mobile', 'QA', 'Data', 'Security'];

        foreach ($validSpecialties as $spec) {
            $specialty = Specialty::fromString($spec);
            $this->assertEquals($spec, $specialty->value());
        }
    }

    #[Test]
    public function should_reject_invalid_specialty(): void
    {
        $this->expectException(InvalidArgumentException::class);

        Specialty::fromString('InvalidSpecialty');
    }

    #[Test]
    public function should_reject_empty_specialty(): void
    {
        $this->expectException(InvalidArgumentException::class);

        Specialty::fromString('');
    }

    #[Test]
    public function should_provide_list_of_valid_specialties(): void
    {
        $validSpecialties = Specialty::validSpecialties();

        $this->assertContains('Backend', $validSpecialties);
        $this->assertContains('Frontend', $validSpecialties);
    }

    #[Test]
    public function should_compare_specialties(): void
    {
        $specialty1 = Specialty::fromString('Backend');
        $specialty2 = Specialty::fromString('Backend');
        $specialty3 = Specialty::fromString('Frontend');

        $this->assertTrue($specialty1->equals($specialty2));
        $this->assertFalse($specialty1->equals($specialty3));
    }
}
