<?php

namespace Tests\Unit\Evaluators\Domain;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Src\Evaluators\Domain\CandidateAssignment;

class CandidateAssignmentTest extends TestCase
{
    #[Test]
    public function should_create_assignment_with_pending_status(): void
    {
        $assignment = CandidateAssignment::create(5, 1);

        $this->assertEquals(5, $assignment->candidateId());
        $this->assertEquals(1, $assignment->evaluatorId());
        $this->assertTrue($assignment->status()->isPending());
        $this->assertNull($assignment->id());
        $this->assertInstanceOf(\DateTimeImmutable::class, $assignment->assignedAt());
    }

    #[Test]
    public function should_transition_to_in_progress(): void
    {
        $assignment = CandidateAssignment::create(5, 1);

        $assignment->startProgress();

        $this->assertTrue($assignment->status()->isInProgress());
    }

    #[Test]
    public function should_transition_to_completed(): void
    {
        $assignment = CandidateAssignment::create(5, 1);

        $assignment->complete();

        $this->assertTrue($assignment->status()->isCompleted());
    }

    #[Test]
    public function should_transition_to_rejected(): void
    {
        $assignment = CandidateAssignment::create(5, 1);

        $assignment->reject();

        $this->assertTrue($assignment->status()->isRejected());
    }

    #[Test]
    public function should_reconstruct_assignment_from_persistence(): void
    {
        $assignedAt = new \DateTimeImmutable('2025-11-28 10:00:00');

        $assignment = CandidateAssignment::reconstruct(
            1,
            10,
            2,
            'completed',
            $assignedAt
        );

        $this->assertEquals(1, $assignment->id());
        $this->assertEquals(10, $assignment->candidateId());
        $this->assertEquals(2, $assignment->evaluatorId());
        $this->assertTrue($assignment->status()->isCompleted());
        $this->assertEquals($assignedAt, $assignment->assignedAt());
    }
}
