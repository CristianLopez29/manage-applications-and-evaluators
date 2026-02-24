<?php

namespace Tests\Evaluators\Unit;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Src\Evaluators\Domain\Evaluator;
use Src\Candidates\Domain\Exceptions\InvalidEmailException;

class EvaluatorTest extends TestCase
{
    #[Test]
    public function should_create_evaluator_with_valid_data(): void
    {
        $evaluator = Evaluator::register(
            'María González',
            'maria@example.com',
            'Backend'
        );

        $this->assertEquals('María González', $evaluator->name()->value());
        $this->assertEquals('maria@example.com', $evaluator->email()->value());
        $this->assertEquals('Backend', $evaluator->specialty()->value);
        $this->assertNull($evaluator->id());
        $this->assertInstanceOf(\DateTimeImmutable::class, $evaluator->createdAt());
    }

    #[Test]
    public function should_throw_exception_with_invalid_email(): void
    {
        $this->expectException(InvalidEmailException::class);

        Evaluator::register(
            'María González',
            'invalid-email',
            'Backend'
        );
    }

    #[Test]
    public function should_reconstruct_evaluator_from_persistence(): void
    {
        $createdAt = new \DateTimeImmutable('2025-11-28 10:00:00');

        $evaluator = Evaluator::reconstruct(
            1,
            'Pedro Sánchez',
            'pedro@example.com',
            'Frontend',
            $createdAt
        );

        $this->assertEquals(1, $evaluator->id());
        $this->assertEquals('Pedro Sánchez', $evaluator->name()->value());
        $this->assertEquals('pedro@example.com', $evaluator->email()->value());
        $this->assertEquals('Frontend', $evaluator->specialty()->value);
        $this->assertEquals($createdAt, $evaluator->createdAt());
    }
}
