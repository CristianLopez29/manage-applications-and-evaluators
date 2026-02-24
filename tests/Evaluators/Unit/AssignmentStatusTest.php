<?php

namespace Tests\Evaluators\Unit;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Src\Evaluators\Domain\Enums\AssignmentStatus;
use ValueError;

class AssignmentStatusTest extends TestCase
{
    #[Test]
    public function should_have_correct_values(): void
    {
        $this->assertEquals('pending', AssignmentStatus::PENDING->value);
        $this->assertEquals('in_progress', AssignmentStatus::IN_PROGRESS->value);
        $this->assertEquals('completed', AssignmentStatus::COMPLETED->value);
        $this->assertEquals('rejected', AssignmentStatus::REJECTED->value);
    }

    #[Test]
    public function should_create_from_valid_string(): void
    {
        $this->assertEquals(AssignmentStatus::PENDING, AssignmentStatus::from('pending'));
        $this->assertEquals(AssignmentStatus::IN_PROGRESS, AssignmentStatus::from('in_progress'));
    }

    #[Test]
    public function should_throw_exception_for_invalid_value(): void
    {
        $this->expectException(ValueError::class);
        AssignmentStatus::from('Invalid');
    }

    #[Test]
    public function should_check_status_correctly(): void
    {
        $this->assertTrue(AssignmentStatus::PENDING->isPending());
        $this->assertFalse(AssignmentStatus::PENDING->isInProgress());
        $this->assertFalse(AssignmentStatus::PENDING->isCompleted());
        $this->assertFalse(AssignmentStatus::PENDING->isRejected());

        $this->assertTrue(AssignmentStatus::IN_PROGRESS->isInProgress());
        $this->assertFalse(AssignmentStatus::IN_PROGRESS->isPending());
        $this->assertFalse(AssignmentStatus::IN_PROGRESS->isCompleted());
        $this->assertFalse(AssignmentStatus::IN_PROGRESS->isRejected());

        $this->assertTrue(AssignmentStatus::COMPLETED->isCompleted());
        $this->assertFalse(AssignmentStatus::COMPLETED->isPending());
        $this->assertFalse(AssignmentStatus::COMPLETED->isInProgress());
        $this->assertFalse(AssignmentStatus::COMPLETED->isRejected());

        $this->assertTrue(AssignmentStatus::REJECTED->isRejected());
        $this->assertFalse(AssignmentStatus::REJECTED->isPending());
        $this->assertFalse(AssignmentStatus::REJECTED->isInProgress());
        $this->assertFalse(AssignmentStatus::REJECTED->isCompleted());
    }
}
