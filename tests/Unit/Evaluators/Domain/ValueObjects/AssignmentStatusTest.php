<?php

namespace Tests\Unit\Evaluators\Domain\ValueObjects;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use InvalidArgumentException;
use Src\Evaluators\Domain\ValueObjects\AssignmentStatus;

class AssignmentStatusTest extends TestCase
{
    #[Test]
    public function should_create_pending_status(): void
    {
        $status = AssignmentStatus::pending();

        $this->assertEquals('pending', $status->value());
        $this->assertTrue($status->isPending());
    }

    #[Test]
    public function should_create_in_progress_status(): void
    {
        $status = AssignmentStatus::inProgress();

        $this->assertEquals('in_progress', $status->value());
        $this->assertTrue($status->isInProgress());
    }

    #[Test]
    public function should_create_completed_status(): void
    {
        $status = AssignmentStatus::completed();

        $this->assertEquals('completed', $status->value());
        $this->assertTrue($status->isCompleted());
    }

    #[Test]
    public function should_create_rejected_status(): void
    {
        $status = AssignmentStatus::rejected();

        $this->assertEquals('rejected', $status->value());
        $this->assertTrue($status->isRejected());
    }

    #[Test]
    public function should_create_from_string(): void
    {
        $status = AssignmentStatus::fromString('pending');

        $this->assertEquals('pending', $status->value());
    }

    #[Test]
    public function should_reject_invalid_status(): void
    {
        $this->expectException(InvalidArgumentException::class);

        AssignmentStatus::fromString('invalid_status');
    }

    #[Test]
    public function should_compare_statuses(): void
    {
        $status1 = AssignmentStatus::pending();
        $status2 = AssignmentStatus::pending();
        $status3 = AssignmentStatus::completed();

        $this->assertTrue($status1->equals($status2));
        $this->assertFalse($status1->equals($status3));
    }
}
